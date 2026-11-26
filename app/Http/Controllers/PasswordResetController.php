<?php

namespace App\Http\Controllers;

use App\Enums\ForensicUnit;
use App\Models\StaffAccount;
use App\Models\StaffPasswordResetRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public const OTP_EXPIRY_MINUTES = 5;
    public const VERIFIED_TOKEN_EXPIRY_MINUTES = 15;
    public const RESEND_THROTTLE_SECONDS = 60;
    public const MAX_OTP_ATTEMPTS = 5;
    public const APPROVAL_WINDOW_MINUTES = 30;

    /**
     * Display the Forgot Password (OTP) page.
     */
    public function showForgotForm(Request $request)
    {
        if (Auth::check()) {
            return redirect(AuthController::getDashboardUrl(Auth::user()));
        }

        $prefillLoginId = trim((string) $request->query('login_id', ''));
        if ($prefillLoginId !== '') {
            $prefillAccount = $this->findOfficerAccount($prefillLoginId);
            $revInfo = AuthController::getStaffRevocationInfo($prefillAccount);
            if ($prefillAccount && $revInfo !== null) {
                $canonicalId = $prefillAccount->service_id ?: $prefillAccount->username;
                $msg = "Forgot password is disabled while your staff account is revoked. Please see {$revInfo['head_display']} to activate your account first.";
                return redirect(url('/login') . '?login_id=' . urlencode((string) $canonicalId))
                    ->with('error', $msg)
                    ->with('pending_login_id', $canonicalId)
                    ->with('revoked_staff_payload', [
                        'account_revoked' => true,
                        'revoked_reason' => $revInfo['revoked_reason'],
                        'pending_approval' => true,
                        'login_id' => $canonicalId,
                        'fullname' => $prefillAccount->fullname,
                        'service_id' => $prefillAccount->service_id,
                        'unit' => $revInfo['unit'],
                        'unit_head_name' => $revInfo['unit_head_name'],
                        'message' => $msg,
                    ]);
            }
        }

        return view('auth.forgot-password', [
            'prefillLoginId' => $prefillLoginId,
        ]);
    }

    /**
     * Step 1: Validate officer identity.
     * - Staff members: submit a Password Reset Request to their Head of Unit for approval before OTP is sent.
     *   (If already approved within the active approval window, transitions to OTP entry or resends OTP.)
     * - Unit Heads / Command officers: dispatch a 6-digit OTP directly to their registered Gmail.
     */
    public function sendOtp(Request $request)
    {
        $isJson = $request->wantsJson() || $request->expectsJson() || $request->ajax();

        $validated = $request->validate([
            'login_id' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'recovery_email' => ['nullable', 'email', 'max:255'],
            'resend_otp' => ['nullable'],
        ], [
            'login_id.required' => 'Please enter your registered Gmail, Username, or Service ID.',
            'recovery_email.email' => 'Please enter a valid Gmail / email address to receive your OTP code.',
        ]);

        $loginId = trim($validated['login_id']);
        $account = $this->findOfficerAccount($loginId);

        if (!$account) {
            $msg = 'No FIMS officer record matched that Service ID, username, or Gmail address.';
            if ($isJson) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }
            return back()->withErrors(['login_id' => $msg])->onlyInput('login_id');
        }

        // Block password reset completely if the staff account is revoked until Head of Unit activates it
        $revocationInfo = AuthController::getStaffRevocationInfo($account);
        if ($revocationInfo !== null) {
            $canonicalId = $account->service_id ?: $account->username;
            $revokedMsg = "Forgot password is disabled because your staff account has been revoked. Please see {$revocationInfo['head_display']} to activate your account first.";
            $loginRedirectUrl = url('/login') . '?login_id=' . urlencode((string) $canonicalId);

            if ($isJson) {
                return response()->json([
                    'success' => false,
                    'account_revoked' => true,
                    'redirect_url' => $loginRedirectUrl,
                    'message' => $revokedMsg,
                ], 403);
            }

            return redirect($loginRedirectUrl)
                ->with('error', $revokedMsg)
                ->with('pending_login_id', $canonicalId);
        }

        $recipientEmail = static::resolveAccountEmailStatic($account);

        // If no email is stored on this officer account yet, verify phone number + capture Gmail address
        if (!$recipientEmail) {
            $submittedPhone = preg_replace('/\D/', '', (string) ($validated['phone_number'] ?? ''));
            $submittedEmail = strtolower(trim((string) ($validated['recovery_email'] ?? '')));

            if ($submittedPhone === '' || $submittedEmail === '') {
                $maskedPhone = $this->maskPhoneNumber((string) ($account->phone_number ?? ''));
                $promptMsg = 'No email address is linked to this officer account yet. Confirm your registered phone number and provide the Gmail address where your OTP code should be sent.';

                if ($isJson) {
                    return response()->json([
                        'success' => false,
                        'needs_recovery_email' => true,
                        'masked_phone' => $maskedPhone,
                        'service_id' => $account->service_id,
                        'unit' => $account->unit,
                        'message' => $promptMsg,
                    ], 200);
                }

                return back()
                    ->withInput()
                    ->with('needs_recovery_email', true)
                    ->with('masked_phone', $maskedPhone)
                    ->with('warning', $promptMsg);
            }

            $storedPhone = preg_replace('/\D/', '', (string) ($account->phone_number ?? ''));
            if ($storedPhone === '' || !hash_equals($storedPhone, $submittedPhone)) {
                $errMsg = 'The phone number entered does not match the 10-digit number registered on this officer account.';
                if ($isJson) {
                    return response()->json([
                        'success' => false,
                        'needs_recovery_email' => true,
                        'masked_phone' => $this->maskPhoneNumber((string) ($account->phone_number ?? '')),
                        'service_id' => $account->service_id,
                        'unit' => $account->unit,
                        'message' => $errMsg,
                    ], 422);
                }
                return back()
                    ->withInput()
                    ->with('needs_recovery_email', true)
                    ->withErrors(['phone_number' => $errMsg]);
            }

            if ($this->isEmailTakenByAnotherOfficer($submittedEmail, $account)) {
                $errMsg = 'That email address is already linked to another officer account.';
                if ($isJson) {
                    return response()->json([
                        'success' => false,
                        'needs_recovery_email' => true,
                        'masked_phone' => $this->maskPhoneNumber((string) ($account->phone_number ?? '')),
                        'service_id' => $account->service_id,
                        'unit' => $account->unit,
                        'message' => $errMsg,
                    ], 422);
                }
                return back()
                    ->withInput()
                    ->with('needs_recovery_email', true)
                    ->withErrors(['recovery_email' => $errMsg]);
            }

            $account->email = $submittedEmail;
            $account->save();
            $recipientEmail = $submittedEmail;
        }

        $acctKey = static::buildAccountKeyStatic($account);
        $maskedEmail = static::maskEmailStatic($recipientEmail);
        $requiresHeadApproval = ($account instanceof StaffAccount) || !(bool) ($account->is_head ?? false);

        if ($requiresHeadApproval && Schema::hasTable('staff_password_reset_requests')) {
            $isResendRequest = $request->boolean('resend_otp');

            // Check if this staff officer already has a recent Unit-Head-approved reset request
            $approvedRequestQuery = StaffPasswordResetRequest::query()
                ->where('status', StaffPasswordResetRequest::STATUS_APPROVED)
                ->where('reviewed_at', '>=', now()->subMinutes(self::APPROVAL_WINDOW_MINUTES));

            if ($account instanceof StaffAccount) {
                $approvedRequestQuery->where('staff_account_id', $account->id);
            } else {
                $approvedRequestQuery->where('user_id', $account->id);
            }

            $approvedRequest = $approvedRequestQuery->latest('id')->first();
            $existingTokenRow = DB::table('password_reset_tokens')->where('email', $acctKey)->first();
            $hasActiveOtp = $existingTokenRow
                && !empty($existingTokenRow->created_at)
                && str_starts_with((string) $existingTokenRow->token, 'otp:')
                && Carbon::parse($existingTokenRow->created_at)->addMinutes(self::OTP_EXPIRY_MINUTES)->isFuture();

            // 1. If Head already approved and the 5-minute OTP in Gmail is still active (and user didn't click Resend),
            //    take the staff member directly to Step 2 so they can enter the OTP from their Gmail.
            if ($approvedRequest && $hasActiveOtp && !$isResendRequest) {
                $elapsed = max(0, (int) Carbon::parse($existingTokenRow->created_at)->diffInSeconds(now(), false));
                $cooldownLeft = max(0, self::RESEND_THROTTLE_SECONDS - $elapsed);
                $readyMsg = "Your Head of Unit approved your request. Enter the 6-digit OTP code sent to {$maskedEmail}.";

                if ($isJson) {
                    return response()->json([
                        'success' => true,
                        'awaiting_head_approval' => false,
                        'acct' => $acctKey,
                        'masked_email' => $maskedEmail,
                        'approved_by' => $approvedRequest->reviewed_by_name,
                        'expiry_minutes' => self::OTP_EXPIRY_MINUTES,
                        'cooldown_seconds' => $cooldownLeft,
                        'officer' => [
                            'service_id' => $account->service_id,
                            'unit' => $account->unit,
                        ],
                        'message' => $readyMsg,
                    ]);
                }

                return back()
                    ->with('status', $readyMsg)
                    ->with('otp_acct', $acctKey)
                    ->with('reset_dispatched_email', $maskedEmail);
            }

            // 2. If Head already approved within the 30-minute window and officer clicked Resend OTP (or 5-min OTP expired),
            //    allow issuing a fresh 5-minute OTP without requiring a second Head approval.
            if ($approvedRequest && ($isResendRequest || !$hasActiveOtp)) {
                if ($existingTokenRow && !empty($existingTokenRow->created_at) && str_starts_with((string) $existingTokenRow->token, 'otp:')) {
                    $secondsElapsed = Carbon::parse($existingTokenRow->created_at)->diffInSeconds(now(), false);
                    if ($secondsElapsed >= 0 && $secondsElapsed < self::RESEND_THROTTLE_SECONDS) {
                        $waitSeconds = (int) ceil(self::RESEND_THROTTLE_SECONDS - $secondsElapsed);
                        $throttleMsg = "An OTP code was just sent. Please wait {$waitSeconds} seconds before requesting a new code.";
                        if ($isJson) {
                            return response()->json([
                                'success' => false,
                                'retry_after' => $waitSeconds,
                                'message' => $throttleMsg,
                            ], 429);
                        }
                        return back()->withErrors(['login_id' => $throttleMsg])->onlyInput('login_id');
                    }
                }

                try {
                    static::dispatchOtpEmailToAccount($account, $recipientEmail, $request->ip());
                    $approvedRequest->otp_sent_at = now();
                    $approvedRequest->email = $recipientEmail;
                    $approvedRequest->save();
                } catch (\Throwable $e) {
                    $mailErr = 'Unable to send the OTP email right now. Please verify mail connectivity or contact NACOC IT Helpdesk (Ext. 4022).';
                    if ($isJson) {
                        return response()->json(['success' => false, 'message' => $mailErr], 500);
                    }
                    return back()->withErrors(['login_id' => $mailErr])->withInput();
                }

                $resentMsg = "A fresh 6-digit verification code has been sent to {$maskedEmail}.";
                if ($isJson) {
                    return response()->json([
                        'success' => true,
                        'awaiting_head_approval' => false,
                        'acct' => $acctKey,
                        'masked_email' => $maskedEmail,
                        'approved_by' => $approvedRequest->reviewed_by_name,
                        'expiry_minutes' => self::OTP_EXPIRY_MINUTES,
                        'cooldown_seconds' => self::RESEND_THROTTLE_SECONDS,
                        'officer' => [
                            'service_id' => $account->service_id,
                            'unit' => $account->unit,
                        ],
                        'message' => $resentMsg,
                    ]);
                }

                return back()
                    ->with('status', $resentMsg)
                    ->with('otp_acct', $acctKey)
                    ->with('reset_dispatched_email', $maskedEmail);
            }

            // 3. Otherwise: Create or refresh a Pending Head-of-Unit Password Reset Request (NO OTP sent yet!)
            $resolvedUnit = ForensicUnit::resolve($account->unit);
            $canonicalUnit = $resolvedUnit ? $resolvedUnit->value : ($account->unit ?: ForensicUnit::CRIME_SCENE_SUPPORT->value);
            $unitSlug = StaffPasswordResetRequest::normalizeUnitSlug($account->unit);

            $pendingQuery = StaffPasswordResetRequest::query()
                ->where('status', StaffPasswordResetRequest::STATUS_PENDING);

            if ($account instanceof StaffAccount) {
                $pendingQuery->where('staff_account_id', $account->id);
            } else {
                $pendingQuery->where('user_id', $account->id);
            }

            $resetRequest = $pendingQuery->latest('id')->first() ?? new StaffPasswordResetRequest();
            $resetRequest->fill([
                'staff_account_id' => $account instanceof StaffAccount ? $account->id : null,
                'user_id'          => $account instanceof User ? $account->id : null,
                'fullname'         => $account->fullname ?? 'Staff Officer',
                'username'         => $account->username,
                'service_id'       => $account->service_id,
                'email'            => $recipientEmail,
                'phone_number'     => $account->phone_number,
                'unit'             => $canonicalUnit,
                'unit_slug'        => $unitSlug,
                'status'           => StaffPasswordResetRequest::STATUS_PENDING,
                'ip_address'       => $request->ip(),
                'user_agent'       => substr((string) $request->userAgent(), 0, 500),
                'reviewed_by_id'   => null,
                'reviewed_by_name' => null,
                'reviewed_at'      => null,
                'review_note'      => null,
                'otp_sent_at'      => null,
                'completed_at'     => null,
            ]);
            $resetRequest->updated_at = now();
            $resetRequest->save();

            // Clear any unapproved token row for safety
            DB::table('password_reset_tokens')->where('email', $acctKey)->delete();

            $unitHead = $this->findUnitHeadForUnit($canonicalUnit);
            $unitHeadDisplay = $unitHead?->fullname
                ? "{$unitHead->fullname} ({$canonicalUnit} Head)"
                : "{$canonicalUnit} Head";

            $pendingMsg = "Password reset request sent to {$unitHeadDisplay}. Once approved, a 6-digit OTP code will be sent to {$maskedEmail}.";

            if ($isJson) {
                return response()->json([
                    'success' => true,
                    'awaiting_head_approval' => true,
                    'request_id' => $resetRequest->id,
                    'acct' => $acctKey,
                    'masked_email' => $maskedEmail,
                    'officer' => [
                        'fullname' => $account->fullname,
                        'service_id' => $account->service_id,
                        'unit' => $canonicalUnit,
                        'unit_head' => $unitHeadDisplay,
                    ],
                    'message' => $pendingMsg,
                ]);
            }

            return back()
                ->with('status', $pendingMsg)
                ->with('pending_reset_request_id', $resetRequest->id)
                ->with('otp_acct', $acctKey)
                ->with('pending_masked_email', $maskedEmail)
                ->with('pending_unit_head', $unitHeadDisplay);
        }

        // Unit Heads / Directorate Command: direct 6-digit OTP to registered Gmail (with 60s throttle)
        $existingRow = DB::table('password_reset_tokens')->where('email', $acctKey)->first();
        if ($existingRow && !empty($existingRow->created_at) && str_starts_with((string) $existingRow->token, 'otp:')) {
            $secondsElapsed = Carbon::parse($existingRow->created_at)->diffInSeconds(now(), false);
            if ($secondsElapsed >= 0 && $secondsElapsed < self::RESEND_THROTTLE_SECONDS) {
                $waitSeconds = (int) ceil(self::RESEND_THROTTLE_SECONDS - $secondsElapsed);
                $throttleMsg = "An OTP code was just sent. Please wait {$waitSeconds} seconds before requesting a new code.";
                if ($isJson) {
                    return response()->json([
                        'success' => false,
                        'retry_after' => $waitSeconds,
                        'message' => $throttleMsg,
                    ], 429);
                }
                return back()->withErrors(['login_id' => $throttleMsg])->onlyInput('login_id');
            }
        }

        try {
            static::dispatchOtpEmailToAccount($account, $recipientEmail, $request->ip());
        } catch (\Throwable $e) {
            $mailErr = 'Unable to send the OTP email right now. Please verify mail connectivity or contact NACOC IT Helpdesk (Ext. 4022).';
            if ($isJson) {
                return response()->json([
                    'success' => false,
                    'message' => $mailErr,
                ], 500);
            }
            return back()->withErrors(['login_id' => $mailErr])->withInput();
        }

        $successMsg = "A 6-digit verification code has been sent to {$maskedEmail}.";

        if ($isJson) {
            return response()->json([
                'success' => true,
                'awaiting_head_approval' => false,
                'acct' => $acctKey,
                'masked_email' => $maskedEmail,
                'expiry_minutes' => self::OTP_EXPIRY_MINUTES,
                'cooldown_seconds' => self::RESEND_THROTTLE_SECONDS,
                'officer' => [
                    'service_id' => $account->service_id,
                    'unit' => $account->unit,
                ],
                'message' => $successMsg,
            ]);
        }

        return back()
            ->with('status', $successMsg)
            ->with('otp_acct', $acctKey)
            ->with('reset_dispatched_email', $maskedEmail);
    }

    /**
     * Poll endpoint for staff waiting on Head of Unit approval on `/forgot-password`.
     */
    public function checkRequestStatus(Request $request)
    {
        if (!Schema::hasTable('staff_password_reset_requests')) {
            return response()->json(['success' => false, 'status' => 'unavailable'], 404);
        }

        $requestId = (int) $request->query('request_id', 0);
        $acctKey = trim((string) $request->query('acct', ''));

        if ($requestId <= 0 || $acctKey === '') {
            return response()->json([
                'success' => false,
                'message' => 'Missing password reset request identifier.',
            ], 422);
        }

        $resetRequest = StaffPasswordResetRequest::find($requestId);
        if (!$resetRequest) {
            return response()->json([
                'success' => false,
                'status' => 'not_found',
                'message' => 'Password reset request could not be found.',
            ], 404);
        }

        $expectedAcct = $resetRequest->staff_account_id
            ? 'staff:' . $resetRequest->staff_account_id
            : 'user:' . $resetRequest->user_id;

        if (!hash_equals($expectedAcct, $acctKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized request status lookup.',
            ], 403);
        }

        $maskedEmail = static::maskEmailStatic((string) $resetRequest->email);
        $cooldownLeft = self::RESEND_THROTTLE_SECONDS;

        if ($resetRequest->status === StaffPasswordResetRequest::STATUS_APPROVED && $resetRequest->otp_sent_at) {
            $elapsed = max(0, (int) $resetRequest->otp_sent_at->diffInSeconds(now(), false));
            $cooldownLeft = max(0, self::RESEND_THROTTLE_SECONDS - $elapsed);
        }

        return response()->json([
            'success' => true,
            'request_id' => $resetRequest->id,
            'status' => $resetRequest->status,
            'acct' => $expectedAcct,
            'masked_email' => $maskedEmail,
            'approved_by' => $resetRequest->reviewed_by_name,
            'review_note' => $resetRequest->review_note,
            'cooldown_seconds' => $cooldownLeft,
            'expiry_minutes' => self::OTP_EXPIRY_MINUTES,
            'message' => match ($resetRequest->status) {
                StaffPasswordResetRequest::STATUS_APPROVED => "Approved by " . ($resetRequest->reviewed_by_name ?: 'your Head of Unit') . ". Enter the 6-digit OTP sent to {$maskedEmail}.",
                StaffPasswordResetRequest::STATUS_DECLINED => 'Your password reset request was declined by ' . ($resetRequest->reviewed_by_name ?: 'your Head of Unit') . ($resetRequest->review_note ? ": {$resetRequest->review_note}" : '.'),
                StaffPasswordResetRequest::STATUS_COMPLETED => 'This password reset request has already been completed.',
                default => 'Awaiting approval from your Head of Unit.',
            },
        ]);
    }

    /**
     * Step 2: Verify the 6-digit OTP code and issue a short-lived verified reset token.
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'acct' => ['nullable', 'string', 'max:100'],
            'login_id' => ['nullable', 'string', 'max:255'],
            'otp' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ], [
            'otp.required' => 'Please enter the 6-digit OTP verification code sent to your Gmail.',
            'otp.regex' => 'The verification code must be exactly 6 digits.',
        ]);

        $account = $this->resolveAccountByKey(
            trim((string) ($validated['acct'] ?? '')),
            trim((string) ($validated['login_id'] ?? ''))
        );

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Officer session could not be matched. Please enter your Service ID or Gmail again.',
            ], 422);
        }

        $acctKey = static::buildAccountKeyStatic($account);
        $row = DB::table('password_reset_tokens')->where('email', $acctKey)->first();

        if (!$row || empty($row->token) || !str_starts_with((string) $row->token, 'otp:')) {
            return response()->json([
                'success' => false,
                'expired' => true,
                'message' => 'No active OTP code found for this account. Please click Resend OTP to receive a fresh code.',
            ], 422);
        }

        if (empty($row->created_at) || Carbon::parse($row->created_at)->addMinutes(self::OTP_EXPIRY_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $acctKey)->delete();
            return response()->json([
                'success' => false,
                'expired' => true,
                'message' => 'This 6-digit OTP code has expired (codes are valid for ' . self::OTP_EXPIRY_MINUTES . ' minutes). Please request a new code.',
            ], 422);
        }

        // Format: otp:<bcrypt_hash>:<attempts>
        $rawToken = (string) $row->token;
        $lastColon = strrpos($rawToken, ':');
        $otpHash = substr($rawToken, 4, $lastColon - 4);
        $attempts = (int) substr($rawToken, $lastColon + 1);

        if (!Hash::check($validated['otp'], $otpHash)) {
            $attempts += 1;
            if ($attempts >= self::MAX_OTP_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('email', $acctKey)->delete();
                return response()->json([
                    'success' => false,
                    'locked' => true,
                    'message' => 'Too many incorrect OTP attempts. For security, this code has been invalidated — please resend a new OTP.',
                ], 422);
            }

            DB::table('password_reset_tokens')
                ->where('email', $acctKey)
                ->update(['token' => 'otp:' . $otpHash . ':' . $attempts]);

            $remaining = self::MAX_OTP_ATTEMPTS - $attempts;
            return response()->json([
                'success' => false,
                'message' => "Invalid OTP verification code. You have {$remaining} attempt" . ($remaining === 1 ? '' : 's') . ' remaining.',
            ], 422);
        }

        // OTP matched — rotate into a short-lived verified reset session token
        $verifiedToken = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $acctKey],
            [
                'token' => 'ver:' . Hash::make($verifiedToken),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'acct' => $acctKey,
            'reset_token' => $verifiedToken,
            'officer' => [
                'fullname' => $account->fullname,
                'username' => $account->username,
                'service_id' => $account->service_id,
                'unit' => $account->unit,
                'role_label' => $account->is_head ? 'Head of Unit' : 'Staff Officer',
            ],
            'message' => 'OTP code verified. Please set your new FIMS password.',
        ]);
    }

    /**
     * Step 3: Save the officer's new password after OTP verification.
     */
    public function resetPassword(Request $request)
    {
        $isJson = $request->wantsJson() || $request->expectsJson() || $request->ajax();

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'acct' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Your new password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        [$account, $tokenStatus] = $this->verifyAuthorizedResetToken(
            $validated['token'],
            trim((string) ($validated['acct'] ?? '')),
            trim((string) ($validated['email'] ?? ''))
        );

        if (!$account || $tokenStatus !== 'valid') {
            $errMsg = $tokenStatus === 'expired'
                ? 'Your OTP verification session timed out. Please verify a new OTP code to reset your password.'
                : 'Invalid or already used password reset session. Please verify a new OTP code.';

            if ($isJson) {
                return response()->json([
                    'success' => false,
                    'expired' => true,
                    'message' => $errMsg,
                ], 422);
            }

            return redirect()->route('password.request')->with('error', $errMsg);
        }

        // Enforce NACOC FIMS security policy: password must not contain username or parts of full name
        $lowerPassword = strtolower($validated['password']);
        $usernameVal = strtolower((string) ($account->username ?? ''));
        $fullnameVal = strtolower((string) ($account->fullname ?? ''));

        if (strlen($usernameVal) >= 3 && str_contains($lowerPassword, $usernameVal)) {
            throw ValidationException::withMessages([
                'password' => 'Security Policy: Your new password must not contain your username.',
            ]);
        }

        $nameParts = preg_split('/\s+/', $fullnameVal) ?: [];
        foreach ($nameParts as $part) {
            if (strlen($part) >= 3 && str_contains($lowerPassword, $part)) {
                throw ValidationException::withMessages([
                    'password' => 'Security Policy: Your new password must not contain your name (' . $part . ').',
                ]);
            }
        }

        $account->password = Hash::make($validated['password']);
        $account->setRememberToken(Str::random(60));
        $account->save();

        $acctKey = static::buildAccountKeyStatic($account);
        DB::table('password_reset_tokens')->where('email', $acctKey)->delete();
        if (!empty($account->email)) {
            DB::table('password_reset_tokens')->where('email', strtolower($account->email))->delete();
        }

        // Mark any pending/approved password reset requests for this officer as completed
        if (Schema::hasTable('staff_password_reset_requests')) {
            $completeQuery = StaffPasswordResetRequest::query()
                ->whereIn('status', [
                    StaffPasswordResetRequest::STATUS_PENDING,
                    StaffPasswordResetRequest::STATUS_APPROVED,
                ]);

            if ($account instanceof StaffAccount) {
                $completeQuery->where('staff_account_id', $account->id);
            } else {
                $completeQuery->where('user_id', $account->id);
            }

            $completeQuery->update([
                'status' => StaffPasswordResetRequest::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }

        $statusMsg = 'Your FIMS password has been updated. Sign in below with your new password.';
        $loginUrl = url('/login') . '?login_id=' . urlencode((string) ($account->service_id ?: $account->username));

        if ($isJson) {
            session()->flash('status', $statusMsg);
            return response()->json([
                'success' => true,
                'message' => $statusMsg,
                'redirect_url' => $loginUrl,
            ]);
        }

        return redirect($loginUrl)->with('status', $statusMsg);
    }

    /**
     * Generate a 5-minute 6-digit OTP, persist its hash in `password_reset_tokens`, and email it to the officer's Gmail.
     */
    public static function dispatchOtpEmailToAccount(User|StaffAccount $account, string $recipientEmail, ?string $ipAddress = null): string
    {
        $acctKey = static::buildAccountKeyStatic($account);
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $storedPayload = 'otp:' . Hash::make($otpCode) . ':0';

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $acctKey],
            [
                'token' => $storedPayload,
                'created_at' => now(),
            ]
        );

        try {
            Mail::send('emails.password_reset', [
                'officerName' => $account->fullname ?? 'Officer',
                'serviceId' => $account->service_id ?? 'N/A',
                'unitName' => $account->unit ?? 'Forensic Division',
                'otpCode' => $otpCode,
                'expiryMinutes' => self::OTP_EXPIRY_MINUTES,
                'requestedAtUtc' => now()->utc()->format('Y-m-d H:i:s \U\T\C'),
                'requestIp' => $ipAddress ?? request()->ip() ?? '127.0.0.1',
            ], function ($message) use ($recipientEmail, $account) {
                $message->to($recipientEmail, $account->fullname ?? 'NACOC Officer')
                    ->subject('NACOC FIMS — Password Reset Verification Code (OTP)');
            });
        } catch (\Throwable $e) {
            DB::table('password_reset_tokens')->where('email', $acctKey)->delete();
            Log::error('FIMS password reset OTP dispatch failed: ' . $e->getMessage());
            throw $e;
        }

        return $otpCode;
    }

    public static function buildAccountKeyStatic(User|StaffAccount $account): string
    {
        return ($account instanceof StaffAccount ? 'staff:' : 'user:') . $account->getKey();
    }

    public static function resolveAccountEmailStatic(User|StaffAccount $account): ?string
    {
        $email = trim((string) ($account->email ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return strtolower($email);
        }

        $username = trim((string) ($account->username ?? ''));
        if ($username !== '' && filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return strtolower($username);
        }

        return null;
    }

    public static function maskEmailStatic(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return $email;
        }

        [$local, $domain] = $parts;
        $len = strlen($local);
        if ($len <= 2) {
            $maskedLocal = substr($local, 0, 1) . '***';
        } else {
            $maskedLocal = substr($local, 0, 2) . str_repeat('*', min(5, max(3, $len - 3))) . substr($local, -1);
        }

        return $maskedLocal . '@' . $domain;
    }

    private function findUnitHeadForUnit(?string $unitName): ?User
    {
        $opSlug = ForensicUnit::resolveOperationalSlug($unitName);
        if ($opSlug !== null) {
            $terms = ForensicUnit::operationalSearchTerms($opSlug);
            return User::where('is_head', true)
                ->where('is_approved', true)
                ->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhereRaw('LOWER(COALESCE(unit, "")) LIKE ?', ['%' . $term . '%']);
                    }
                })
                ->first();
        }

        return User::where('is_head', true)
            ->where('is_approved', true)
            ->where(function ($q) {
                $q->where('unit', 'LIKE', '%Forensic Directorate%')
                    ->orWhere('unit', 'LIKE', '%Forensic Head%');
            })
            ->first();
    }

    /**
     * Find an officer across `users` (Unit Heads / Command) and `staff_accounts` (Staff Members).
     */
    private function findOfficerAccount(string $loginId): User|StaffAccount|null
    {
        $lower = strtolower(trim($loginId));
        if ($lower === '') {
            return null;
        }

        $user = User::whereRaw('LOWER(service_id) = ?', [$lower])
            ->orWhereRaw('LOWER(username) = ?', [$lower])
            ->orWhereRaw("LOWER(COALESCE(email, '')) = ?", [$lower])
            ->first();

        if ($user) {
            return $user;
        }

        if (Schema::hasTable('staff_accounts')) {
            return StaffAccount::whereRaw('LOWER(service_id) = ?', [$lower])
                ->orWhereRaw('LOWER(username) = ?', [$lower])
                ->orWhereRaw("LOWER(COALESCE(email, '')) = ?", [$lower])
                ->first();
        }

        return null;
    }

    private function resolveAccountByKey(string $acctKey, string $fallbackIdentifier = ''): User|StaffAccount|null
    {
        if (str_starts_with($acctKey, 'user:')) {
            $id = (int) substr($acctKey, 5);
            return $id > 0 ? User::find($id) : null;
        }

        if (str_starts_with($acctKey, 'staff:')) {
            if (!Schema::hasTable('staff_accounts')) {
                return null;
            }
            $id = (int) substr($acctKey, 6);
            return $id > 0 ? StaffAccount::find($id) : null;
        }

        if ($fallbackIdentifier !== '') {
            return $this->findOfficerAccount($fallbackIdentifier);
        }

        return null;
    }

    /**
     * @return array{0: User|StaffAccount|null, 1: 'valid'|'expired'|'invalid'}
     */
    private function verifyAuthorizedResetToken(string $plainToken, string $acctKey, string $fallbackIdentifier = ''): array
    {
        $account = $this->resolveAccountByKey($acctKey, $fallbackIdentifier);
        if (!$account) {
            return [null, 'invalid'];
        }

        $canonicalKey = static::buildAccountKeyStatic($account);
        $tokenRow = DB::table('password_reset_tokens')->where('email', $canonicalKey)->first();

        if (!$tokenRow || empty($tokenRow->token) || !str_starts_with((string) $tokenRow->token, 'ver:')) {
            return [$account, 'invalid'];
        }

        $verHash = substr((string) $tokenRow->token, 4);
        if (!Hash::check($plainToken, $verHash)) {
            return [$account, 'invalid'];
        }

        if (empty($tokenRow->created_at)) {
            return [$account, 'expired'];
        }

        $expiresAt = Carbon::parse($tokenRow->created_at)->addMinutes(self::VERIFIED_TOKEN_EXPIRY_MINUTES);
        if ($expiresAt->isPast()) {
            DB::table('password_reset_tokens')->where('email', $canonicalKey)->delete();
            return [$account, 'expired'];
        }

        return [$account, 'valid'];
    }

    private function isEmailTakenByAnotherOfficer(string $email, User|StaffAccount $current): bool
    {
        $lower = strtolower(trim($email));

        $userHit = User::whereRaw("LOWER(COALESCE(email, '')) = ?", [$lower])
            ->when($current instanceof User, fn ($q) => $q->where('id', '!=', $current->id))
            ->exists();

        if ($userHit) {
            return true;
        }

        if (Schema::hasTable('staff_accounts')) {
            return StaffAccount::whereRaw("LOWER(COALESCE(email, '')) = ?", [$lower])
                ->when($current instanceof StaffAccount, fn ($q) => $q->where('id', '!=', $current->id))
                ->exists();
        }

        return false;
    }

    private function maskPhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $len = strlen($digits);
        if ($len < 2) {
            return '••• ••• ••••';
        }

        if ($len === 10) {
            return substr($digits, 0, 1) . '•• ••• •••' . substr($digits, -1);
        }

        return substr($digits, 0, 1) . str_repeat('•', max(1, $len - 2)) . substr($digits, -1);
    }
}
