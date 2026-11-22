<?php

namespace App\Http\Controllers;

use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use App\Models\Unit;

class AuthController extends Controller
{
    /**
     * Get the appropriate dashboard URL for a user based on role & unit.
     */
    public static function getDashboardUrl($user)
    {
        if (!$user) {
            return '/login';
        }

        $unit = (string) ($user->unit ?? '');
        $unitLower = strtolower(trim($unit));

        if ($user->is_head && (
            $unitLower === 'office of the director-general' ||
            $unitLower === 'director general' ||
            $unitLower === 'dg' ||
            str_contains($unitLower, 'director general') ||
            str_contains($unitLower, 'director-general')
        )) {
            return '/DG';
        }

        $resolvedUnit = \App\Enums\ForensicUnit::resolve($unit);

        if ($user->is_head && ($resolvedUnit === \App\Enums\ForensicUnit::FORENSIC_DIRECTORATE || str_contains($unit, 'Forensic Directorate'))) {
            return '/Forensic_Head';
        }

        return match ($resolvedUnit) {
            \App\Enums\ForensicUnit::CRIME_SCENE_SUPPORT        => $user->is_head ? '/Crime_Scene_Support/head' : '/Crime_Scene_Support/staff/dashboard',
            \App\Enums\ForensicUnit::LAB_ANALYSIS                => $user->is_head ? '/Lab_Analysis' : '/Lab_Analysis/staff/dashboard',
            \App\Enums\ForensicUnit::EVIDENCE_MANAGEMENT         => $user->is_head ? '/Evidence_Management' : '/Evidence_Management/staff/dashboard',
            \App\Enums\ForensicUnit::DIGITAL_PHYSICAL_FORENSICS  => $user->is_head ? '/Digital_Forensics' : '/Digital_Forensics/staff/dashboard',
            \App\Enums\ForensicUnit::FORENSIC_DIRECTORATE        => $user->is_head ? '/Forensic_Head' : '/Forensic_Head/staff/dashboard',
            default                                              => $user->is_head ? '/Crime_Scene_Support/head' : '/Crime_Scene_Support/staff/dashboard',
        };
    }

    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect(self::getDashboardUrl(Auth::user()));
        }
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt against database.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginId = trim($credentials['login_id']);
        $password = $credentials['password'];

        // Case-insensitive lookup by service_id, username, or email (heads in users, staff in staff_accounts)
        $user = User::whereRaw('LOWER(service_id) = ?', [strtolower($loginId)])
            ->orWhereRaw('LOWER(username) = ?', [strtolower($loginId)])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($loginId)])
            ->first();

        if (!$user && \Illuminate\Support\Facades\Schema::hasTable('staff_accounts')) {
            $user = StaffAccount::whereRaw('LOWER(service_id) = ?', [strtolower($loginId)])
                ->orWhereRaw('LOWER(username) = ?', [strtolower($loginId)])
                ->orWhereRaw('LOWER(email) = ?', [strtolower($loginId)])
                ->first();
        }

        $isJsonReq = $request->wantsJson() || $request->expectsJson() || $request->is('api/*') || str_contains($request->header('Accept', ''), 'application/json');

        if ($user && Hash::check($password, $user->password)) {
            // Block login if user account is unapproved (pending Head of Unit approval) or deactivated
            if (isset($user->is_approved) && !$user->is_approved) {
                $isPendingStaff = (bool) ($user->is_staff && !$user->is_head);
                $blockedMessage = $isPendingStaff
                    ? 'Pending Approval: Your staff account must be approved by your Head of Unit before you can sign in.'
                    : 'Account Deactivated: Your account access has been suspended. Please contact the administrator.';
                $pendingLoginId = $isPendingStaff ? ($user->service_id ?: $user->username) : null;

                if ($isJsonReq) {
                    return response()->json([
                        'success' => false,
                        'message' => $blockedMessage,
                        'pending_approval' => $isPendingStaff,
                        'login_id' => $pendingLoginId,
                    ], 403);
                }

                $redirect = back()->withErrors([
                    'login_id' => $blockedMessage,
                ])->onlyInput('login_id');

                return $pendingLoginId
                    ? $redirect->with('pending_login_id', $pendingLoginId)
                    : $redirect;
            }

            $existingSessionId = $request->session()->getId();
            Auth::login($user, $request->has('remember'));
            if ($existingSessionId) {
                $request->session()->setId($existingSessionId);
            }
            if ($user instanceof StaffAccount) {
                $user->markOnline();
            }

            // Land on the user's dashboard after login
            $request->session()->forget('url.intended');
            $redirectUrl = self::getDashboardUrl($user);

            if ($isJsonReq) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login Successful! Welcome to NACOC Forensic Directorate.',
                    'redirect_url' => $redirectUrl,
                    'user' => [
                        'id' => $user->id,
                        'fullname' => $user->fullname,
                        'service_id' => $user->service_id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'unit' => $user->unit,
                        'is_head' => (bool) $user->is_head,
                        'is_staff' => (bool) $user->is_staff,
                        'is_approved' => (bool) $user->is_approved,
                    ]
                ], 200);
            }

            return redirect($redirectUrl)->with('status', 'Login Successful! Welcome to NACOC Forensic Directorate.');
        }

        if ($isJsonReq) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong username or password.'
            ], 401);
        }

        return back()->withErrors([
            'login_id' => 'Wrong username or password.',
        ])->onlyInput('login_id');
    }

    /**
     * Show the registration form with dynamic units from database.
     * Enforces Head of Forensic to register first.
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect(self::getDashboardUrl(Auth::user()));
        }

        if (Unit::count() === 0) {
            $unitsData = [
                ['name' => 'Crime Scene Support', 'code' => 'CSSU', 'category' => 'FIELD OPS'],
                ['name' => 'Evidence Management', 'code' => 'EMU', 'category' => 'CUSTODY'],
                ['name' => 'Lab Analysis', 'code' => 'LAU', 'category' => 'LAB'],
                ['name' => 'Digital & Physical Forensic Unit', 'code' => 'DPFU', 'category' => 'CYBER & TECH'],
            ];
            foreach ($unitsData as $data) {
                Unit::firstOrCreate(['name' => $data['name']], $data);
            }
        }
        $units = Unit::all();
        $allHeads = User::where('is_head', true)->get();
        $registeredHeads = $allHeads->pluck('fullname', 'unit')->toArray();
        foreach ($units as $unitModel) {
            $matchedHead = $this->findExistingUnitHeadFromCollection($allHeads, $unitModel->name);
            if ($matchedHead) {
                $registeredHeads[$unitModel->name] = $matchedHead->fullname;
            }
        }

        // Check if Head of Forensic has registered
        $forensicHead = User::where('is_head', true)
            ->where(function($q) {
                $q->where('unit', 'Forensic Directorate')
                  ->orWhere('unit', 'Forensic Head')
                  ->orWhere('unit', 'Head of Forensic')
                  ->orWhere('unit', 'Directorate Head')
                  ->orWhere('unit', 'LIKE', '%Forensic Directorate%');
            })
            ->first();

        $hasForensicHead = !is_null($forensicHead);

        if ($hasForensicHead) {
            // Remove Head of Forensic from the units list if already on the system
            $units = $units->filter(function($u) {
                $name = is_object($u) ? $u->name : ($u['name'] ?? '');
                $lower = strtolower(trim($name));
                return !in_array($lower, [
                    'head of forensic',
                    'forensic head',
                    'forensic directorate',
                    'directorate head',
                ], true);
            })->values();
        } else {
            // Include Head of Forensic in the units dropdown list
            $hasHof = $units->contains(function($u) {
                $name = is_object($u) ? $u->name : ($u['name'] ?? '');
                $lower = strtolower(trim($name));
                return in_array($lower, [
                    'head of forensic',
                    'forensic head',
                    'forensic directorate',
                ], true);
            });

            if (!$hasHof) {
                $hofUnit = (object) [
                    'id' => 999,
                    'name' => 'Head of Forensic',
                    'code' => 'HOF',
                    'category' => 'COMMAND',
                    'head_name' => null,
                    'head_title' => 'Head of Forensic Directorate',
                ];
                $units = $units->push($hofUnit);
            }
        }

        // Check if Director General has registered
        $directorGeneral = User::where('is_head', true)
            ->where(function($q) {
                $q->where('unit', 'Office of the Director-General')
                  ->orWhere('unit', 'Director General')
                  ->orWhere('unit', 'DG')
                  ->orWhere('unit', 'LIKE', '%Director General%')
                  ->orWhere('unit', 'LIKE', '%Director-General%');
            })
            ->first();

        $hasDirectorGeneral = !is_null($directorGeneral);

        if ($hasDirectorGeneral) {
            // Remove Director General from the units list if already on the system
            $units = $units->filter(function($u) {
                $name = is_object($u) ? $u->name : ($u['name'] ?? '');
                $lower = strtolower(trim($name));
                return !in_array($lower, [
                    'director general',
                    'director-general',
                    'office of the director-general',
                    'dg',
                ], true);
            })->values();
            $registeredHeads['Director General'] = $directorGeneral->fullname;
            $registeredHeads['Office of the Director-General'] = $directorGeneral->fullname;
        } else {
            // Include Director General in the units dropdown list
            $hasDg = $units->contains(function($u) {
                $name = is_object($u) ? $u->name : ($u['name'] ?? '');
                $lower = strtolower(trim($name));
                return in_array($lower, [
                    'director general',
                    'director-general',
                    'office of the director-general',
                    'dg',
                ], true);
            });

            if (!$hasDg) {
                $dgUnit = (object) [
                    'id' => 998,
                    'name' => 'Director General',
                    'code' => 'DG',
                    'category' => 'SUPREME COMMAND',
                    'head_name' => null,
                    'head_title' => 'Director General of NACOC',
                ];
                $units = $units->push($dgUnit);
            }
        }

        return view('auth.register', compact('units', 'registeredHeads', 'hasForensicHead', 'forensicHead', 'hasDirectorGeneral', 'directorGeneral'));
    }

    /**
     * Handle officer registration and save to database.
     * Enforces Head of Forensic first registration mandate.
     */
    public function register(Request $request)
    {
        $forensicHeadExists = User::where('is_head', true)
            ->where(function($q) {
                $q->where('unit', 'Forensic Directorate')
                  ->orWhere('unit', 'Forensic Head')
                  ->orWhere('unit', 'Head of Forensic')
                  ->orWhere('unit', 'Directorate Head')
                  ->orWhere('unit', 'LIKE', '%Forensic Directorate%');
            })
            ->exists();

        $directorGeneralExists = User::where('is_head', true)
            ->where(function($q) {
                $q->where('unit', 'Office of the Director-General')
                  ->orWhere('unit', 'Director General')
                  ->orWhere('unit', 'DG')
                  ->orWhere('unit', 'LIKE', '%Director General%')
                  ->orWhere('unit', 'LIKE', '%Director-General%');
            })
            ->exists();

        $unitInput = trim($request->input('unit', ''));
        $isHeadOfForensic = in_array(strtolower($unitInput), [
            'head of forensic',
            'forensic head',
            'forensic directorate',
            'directorate head',
        ], true);

        $isDirectorGeneral = in_array(strtolower($unitInput), [
            'director general',
            'director-general',
            'office of the director-general',
            'dg',
        ], true);

        // If Forensic Head already exists, prevent creating another Forensic Head account
        if ($forensicHeadExists && $isHeadOfForensic) {
            throw ValidationException::withMessages([
                'unit' => 'The Head of Forensic Directorate has already registered. Please select a specialized unit.',
            ]);
        }

        // If Director General already exists, prevent creating another Director General account
        if ($directorGeneralExists && $isDirectorGeneral) {
            throw ValidationException::withMessages([
                'unit' => 'The Director-General has already registered. Only one Director-General account is allowed.',
            ]);
        }

        if ($isHeadOfForensic) {
            $request->merge([
                'unit' => 'Forensic Directorate',
                'role' => 'head',
                'is_staff' => 0,
            ]);
        } elseif ($isDirectorGeneral) {
            $request->merge([
                'unit' => 'Office of the Director-General',
                'role' => 'head',
                'is_staff' => 0,
            ]);
        }

        if ($request->has('service_prefix') && $request->has('service_number')) {
            $prefix = strtoupper(trim($request->input('service_prefix')));
            $number = trim($request->input('service_number'));
            if (!empty($number)) {
                $request->merge([
                    'service_id' => $prefix . '-' . $number,
                ]);
            }
        }

        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'service_id' => ['required', 'string', 'regex:' . config('forensics.service_id.regex', '/^(SD|JD|SVC)-[0-9]{3,}$/i'), 'unique:users,service_id', 'unique:staff_accounts,service_id'],
            'phone_number' => ['required', 'string', 'regex:' . config('forensics.service_id.phone_regex', '/^[0-9]{10}$/')],
            'unit' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'username.unique' => 'The username has already been taken.',
            'service_id.required' => 'Please enter your Service ID Number.',
            'service_id.regex' => 'Service ID Number format must be SD-345 or JD-343 (SD or JD followed by digits).',
            'service_id.unique' => 'The Service ID Number has already been registered.',
            'phone_number.regex' => 'Phone Number must be exactly 10 digits (e.g. 0200000000).',
            'terms.accepted' => 'You must agree to the NACOC Security Policy & Confidentiality Mandate before registering.',
        ]);

        // Extra security validation: MUST NOT contain username or name
        $lowerPassword = strtolower($validated['password']);
        $usernameVal = strtolower($validated['username']);
        $fullnameVal = strtolower($validated['fullname']);

        if (strlen($usernameVal) >= 3 && str_contains($lowerPassword, $usernameVal)) {
            throw ValidationException::withMessages([
                'password' => 'Security Violation: Password must NOT contain your username.',
            ]);
        }

        $nameParts = preg_split('/\s+/', $fullnameVal);
        foreach ($nameParts as $part) {
            if (strlen($part) >= 3 && str_contains($lowerPassword, $part)) {
                throw ValidationException::withMessages([
                    'password' => 'Security Violation: Password must NOT contain your full name.',
                ]);
            }
        }

        $roleInput = strtolower(trim($request->input('role', '')));
        if ($roleInput === 'head' || $roleInput === 'unit_head') {
            $isStaff = false;
        } elseif ($roleInput === 'staff' || $roleInput === 'officer') {
            $isStaff = true;
        } else {
            $isStaff = $request->has('is_staff') ? $request->boolean('is_staff') : true;
        }
        $isHead = !$isStaff;

        if ($isHead) {
            $existingUnitHead = $this->findExistingUnitHeadFromCollection(
                User::where('is_head', true)->get(),
                $validated['unit']
            );
            if ($existingUnitHead) {
                $resolvedUnitLabel = \App\Enums\ForensicUnit::resolve($validated['unit'])?->value ?? $validated['unit'];
                throw ValidationException::withMessages([
                    'unit' => "A Head of Unit ({$existingUnitHead->fullname}) is already registered for {$resolvedUnitLabel}. Only one unit head account is allowed per unit.",
                ]);
            }
        }

        // Staff officers require approval from their Head of Unit before they can log in
        $isApproved = $isHead;

        $inputUsername = trim($validated['username']);
        $userEmail = null;

        if (filter_var($inputUsername, FILTER_VALIDATE_EMAIL) || str_contains($inputUsername, '@')) {
            $userEmail = strtolower($inputUsername);
            if (User::where('email', $userEmail)->exists() || StaffAccount::where('email', $userEmail)->exists()) {
                throw ValidationException::withMessages([
                    'username' => 'The email address has already been registered.',
                ]);
            }
        }

        if (StaffAccount::whereRaw('LOWER(username) = ?', [strtolower($inputUsername)])->exists()) {
            throw ValidationException::withMessages([
                'username' => 'The username has already been taken.',
            ]);
        }

        // Store staff member accounts strictly in staff_accounts; store heads in users
        if ($isStaff) {
            $user = StaffAccount::create([
                'fullname' => $validated['fullname'],
                'username' => $inputUsername,
                'email' => $userEmail,
                'service_id' => $validated['service_id'],
                'phone_number' => $validated['phone_number'],
                'is_approved' => $isApproved,
                'unit' => $validated['unit'],
                'password' => Hash::make($validated['password']),
            ]);
        } else {
            $user = User::create([
                'fullname' => $validated['fullname'],
                'username' => $inputUsername,
                'email' => $userEmail,
                'service_id' => $validated['service_id'],
                'phone_number' => $validated['phone_number'],
                'is_staff' => false,
                'is_head' => true,
                'is_approved' => $isApproved,
                'unit' => $validated['unit'],
                'password' => Hash::make($validated['password']),
            ]);
        }

        if ($isHead && in_array($validated['unit'], ['Office of the Director-General', 'Director General', 'DG'])) {
            $statusMsg = 'Director-General registration successful! Supreme command established. Please sign in to access your portal.';
        } elseif ($isHead && in_array($validated['unit'], ['Forensic Directorate', 'Forensic Head', 'Directorate Head'])) {
            $statusMsg = 'Head of Forensic registration successful! Directorate command established. Unit Heads and Staff may now sign up.';
        } elseif ($isStaff) {
            $statusMsg = 'Registration successful! Your staff account requires approval by your Head of Unit before you can sign in.';
        } else {
            $statusMsg = 'Registration successful! Please sign in with your Service ID or Username.';
        }

        $redirect = redirect('/login')->with('status', $statusMsg);
        if ($isStaff && !$isApproved) {
            $redirect->with('pending_login_id', $user->service_id ?: $user->username);
        }

        return $redirect;
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $existingSessionId = $request->session()->getId();
        $currentUser = Auth::user();
        if ($currentUser instanceof StaffAccount) {
            $currentUser->markOffline();
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($existingSessionId) {
            $request->session()->setId($existingSessionId);
        }

        if ($request->wantsJson() || $request->expectsJson() || $request->is('api/*') || str_contains($request->header('Accept', ''), 'application/json')) {
            return response()->json([
                'success' => true,
                'message' => 'Officer logged out successfully.'
            ], 200);
        }

        return redirect('/login')->with('status', 'Officer logged out successfully.');
    }

    /**
     * Find an existing Head of Unit account matching the given unit name or alias.
     */
    private function findExistingUnitHeadFromCollection($heads, string $unitName): ?User
    {
        $targetUnit = \App\Enums\ForensicUnit::resolve($unitName);
        $normalizedName = strtolower(trim($unitName));

        $isDg = in_array($normalizedName, [
            'director general',
            'director-general',
            'office of the director-general',
            'dg',
        ], true);

        if ($isDg) {
            foreach ($heads as $head) {
                $headUnitLower = strtolower(trim((string) $head->unit));
                if (
                    in_array($headUnitLower, ['director general', 'director-general', 'office of the director-general', 'dg'], true) ||
                    str_contains($headUnitLower, 'director general') ||
                    str_contains($headUnitLower, 'director-general')
                ) {
                    return $head;
                }
            }
            return null;
        }

        foreach ($heads as $head) {
            if ($targetUnit !== null) {
                if (\App\Enums\ForensicUnit::resolve($head->unit) === $targetUnit) {
                    return $head;
                }
            } elseif (strtolower(trim((string) $head->unit)) === $normalizedName) {
                return $head;
            }
        }

        return null;
    }
}
