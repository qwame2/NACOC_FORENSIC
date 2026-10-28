<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Show the profile settings page for the authenticated officer.
     */
    public function edit(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Determine user role badge label
        $resolvedUnit = \App\Enums\ForensicUnit::resolve($user->unit ?? '');
        $isForensicHead = $user->is_head && ($resolvedUnit === \App\Enums\ForensicUnit::FORENSIC_DIRECTORATE || str_contains($user->unit ?? '', 'Forensic Directorate'));

        if ($isForensicHead) {
            $roleLabel = 'Head of Forensic Directorate';
            $badgeColor = 'bg-blue-600 text-white';
        } elseif ($user->is_head) {
            $roleLabel = 'Head of Unit (' . ($user->unit ?? 'Unit Head') . ')';
            $badgeColor = 'bg-emerald-600 text-white';
        } else {
            $roleLabel = 'Forensic Field / Investigating Staff Officer';
            $badgeColor = 'bg-slate-700 text-white';
        }

        $isHead = (bool) $user->is_head;
        $userUnit = $user->unit ?? 'Crime Scene Support';

        if ($isForensicHead) {
            $backUrl = url('/Forensic_Head');
        } elseif ($user->is_head) {
            $backUrl = url('/Crime_Scene_Support/head?from=' . urlencode($userUnit));
        } else {
            $backUrl = url('/cases?from=' . urlencode($userUnit));
        }

        $totalCasesCount = count(\App\Models\ForensicCase::allCases());

        // Session information
        $sessionInfo = [
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->header('User-Agent') ?? 'Secure Terminal',
            'last_login' => now()->format('d M Y · H:i:s T'),
        ];

        return view('profile.settings', compact('user', 'isHead', 'isForensicHead', 'userUnit', 'roleLabel', 'badgeColor', 'sessionInfo', 'backUrl', 'totalCasesCount'));
    }

    /**
     * Update officer contact and profile details.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'phone_number' => ['required', 'string', 'regex:' . config('forensics.service_id.phone_regex', '/^[0-9]{10}$/')],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'avatar_data' => ['nullable', 'string'],
            'avatar_file' => ['nullable', 'image', 'mimes:' . config('forensics.upload_limits.allowed_image_mimes', 'jpeg,png,jpg,webp'), 'max:' . config('forensics.upload_limits.avatar_max_kb', 5120)],
            'signature_data' => ['nullable', 'string'],
            'signature_file' => ['nullable', 'image', 'mimes:' . config('forensics.upload_limits.allowed_signature_mimes', 'jpeg,png,jpg,webp,svg'), 'max:' . config('forensics.upload_limits.signature_max_kb', 5120)],
        ], [
            'fullname.required' => 'Full Name is required.',
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken by another officer.',
            'phone_number.required' => 'Phone Number is required.',
            'phone_number.regex' => 'Phone Number must be exactly 10 digits (e.g. 0200000000).',
            'email.email' => 'Please provide a valid official email address.',
            'email.unique' => 'This email address is already in use.',
            'avatar_file.image' => 'The uploaded file must be an image (JPEG, PNG, JPG, or WEBP).',
            'avatar_file.max' => 'Portrait photo size must not exceed 5MB.',
            'signature_file.image' => 'The uploaded signature must be an image (PNG, JPG, SVG, or WEBP).',
            'signature_file.max' => 'Signature file size must not exceed 5MB.',
        ]);

        $user->fullname = trim($validated['fullname']);
        $user->username = trim($validated['username']);
        $user->phone_number = trim($validated['phone_number']);
        $user->email = !empty($validated['email']) ? strtolower(trim($validated['email'])) : null;

        // If an avatar image file or data URI was provided
        if ($request->hasFile('avatar_file')) {
            $file = $request->file('avatar_file');
            if ($file->isValid()) {
                $ext = $file->getClientOriginalExtension();
                $data = file_get_contents($file->getRealPath());
                $mime = $file->getMimeType();
                $user->avatar = 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        } elseif (!empty($validated['avatar_data']) && str_starts_with($validated['avatar_data'], 'data:image')) {
            $user->avatar = $validated['avatar_data'];
        }

        // If a signature file or drawn signature data URI was provided
        if ($request->hasFile('signature_file')) {
            $sigFile = $request->file('signature_file');
            if ($sigFile->isValid()) {
                $sigData = file_get_contents($sigFile->getRealPath());
                $sigMime = $sigFile->getMimeType();
                $user->signature = 'data:' . $sigMime . ';base64,' . base64_encode($sigData);
            }
        } elseif (!empty($validated['signature_data']) && str_starts_with($validated['signature_data'], 'data:image')) {
            $user->signature = $validated['signature_data'];
        }

        $user->save();

        // Update session user name if cached
        session(['user_name' => $user->fullname]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Officer profile updated successfully.',
                'user' => [
                    'fullname' => $user->fullname,
                    'username' => $user->username,
                    'phone_number' => $user->phone_number,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'signature' => $user->signature,
                ]
            ]);
        }

        return back()->with('status', 'Officer profile information successfully updated.');
    }

    /**
     * Update officer password credentials.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'password.required' => 'Please enter a new password.',
            'password.min' => 'New password must be at least 8 characters in length.',
            'password.confirmed' => 'The new password confirmation does not match.',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password provided is incorrect.',
            ]);
        }

        $lowerPassword = strtolower($validated['password']);
        $usernameVal = strtolower($user->username);
        $fullnameVal = strtolower($user->fullname);

        // Security Validation Policy: Password must NOT contain username
        if (strlen($usernameVal) >= 3 && str_contains($lowerPassword, $usernameVal)) {
            throw ValidationException::withMessages([
                'password' => 'Security Policy Violation: Password must not contain your username.',
            ]);
        }

        // Security Validation Policy: Password must NOT contain parts of full name
        $nameParts = preg_split('/\s+/', $fullnameVal);
        foreach ($nameParts as $part) {
            if (strlen($part) >= 3 && str_contains($lowerPassword, $part)) {
                throw ValidationException::withMessages([
                    'password' => 'Security Policy Violation: Password must not contain your name (' . $part . ').',
                ]);
            }
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Password credentials successfully updated.',
            ]);
        }

        return back()->with('status', 'Password credentials successfully changed.');
    }

    /**
     * Remove / reset officer avatar.
     */
    public function removeAvatar(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $user->avatar = null;
        $user->save();

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile portrait cleared. Default initials badge restored.',
            ]);
        }

        return back()->with('status', 'Profile portrait cleared. Default initials badge restored.');
    }

    /**
     * Remove / reset officer official digital signature.
     */
    public function removeSignature(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $user->signature = null;
        $user->save();

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Official digital signature removed successfully.',
            ]);
        }

        return back()->with('status', 'Official digital signature removed successfully.');
    }
}
