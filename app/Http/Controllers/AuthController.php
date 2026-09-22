<?php

namespace App\Http\Controllers;

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

        $unit = $user->unit ?? '';

        $isForensicHead = $user->is_head && (
            $unit === 'Forensic Directorate' ||
            $unit === 'Forensic Head' ||
            $unit === 'Directorate Head' ||
            str_contains($unit, 'Forensic Directorate')
        );

        if ($isForensicHead) {
            return '/Forensic_Head';
        }

        $unitLower = strtolower($unit);

        if (str_contains($unitLower, 'crime') || str_contains($unitLower, 'field') || str_contains($unitLower, 'cssu')) {
            return $user->is_head ? '/Crime_Scene_Support/head' : '/Crime_Scene_Support/staff';
        }

        if (str_contains($unitLower, 'lab') || str_contains($unitLower, 'cflu')) {
            return '/Lab_Analysis/examination';
        }

        if (str_contains($unitLower, 'evidence') || str_contains($unitLower, 'emu')) {
            return '/cases?unit=Evidence+Management';
        }

        if (str_contains($unitLower, 'digital') || str_contains($unitLower, 'physical') || str_contains($unitLower, 'dpfu')) {
            return '/cases?unit=Digital+%26+Physical+Forensics';
        }

        return $user->is_head ? '/Crime_Scene_Support/head' : '/cases';
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

        // Case-insensitive lookup by service_id, username, or email
        $user = User::whereRaw('LOWER(service_id) = ?', [strtolower($loginId)])
            ->orWhereRaw('LOWER(username) = ?', [strtolower($loginId)])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($loginId)])
            ->first();

        $isJsonReq = $request->wantsJson() || $request->expectsJson() || $request->is('api/*') || str_contains($request->header('Accept', ''), 'application/json');

        if ($user && Hash::check($password, $user->password)) {
            $isForensicHead = $user->is_head && (
                $user->unit === 'Forensic Directorate' ||
                $user->unit === 'Forensic Head' ||
                $user->unit === 'Directorate Head' ||
                str_contains($user->unit, 'Forensic Directorate')
            );

            // Block Unit Head login if account is not approved by Head of Forensic Directorate
            if ($user->is_head && !$isForensicHead && isset($user->is_approved) && !$user->is_approved) {
                if ($isJsonReq) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Account Pending Approval: Your Unit Head registration has been submitted to the Head of Forensic Directorate for approval.'
                    ], 403);
                }
                return back()->withErrors([
                    'login_id' => 'Account Pending Approval: Your Unit Head registration has been submitted to the Head of Forensic Directorate for approval. You cannot log in until your account is approved.',
                ])->with('pending_login_id', $loginId)->onlyInput('login_id');
            }

            Auth::login($user, $request->has('remember'));
            $request->session()->regenerate();

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

            // Clean up intended URL if it points back to login / root / register page to prevent redirect loops
            $intended = session()->get('url.intended');
            if ($intended) {
                $intendedPath = parse_url($intended, PHP_URL_PATH) ?? $intended;
                $cleanedPath = rtrim($intendedPath, '/');
                if (in_array($cleanedPath, ['', '/login', '/register', '/api/login', '/api/logout'])) {
                    session()->forget('url.intended');
                }
            }

            return redirect()->intended($redirectUrl)->with('status', 'Login Successful! Welcome to NACOC Forensic Directorate.');
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
                ['name' => 'Lab Analysis', 'code' => 'CFLU', 'category' => 'LAB'],
                ['name' => 'Digital & Physical Forensic Unit', 'code' => 'DPFU', 'category' => 'CYBER & TECH'],
            ];
            foreach ($unitsData as $data) {
                Unit::firstOrCreate(['name' => $data['name']], $data);
            }
        }
        $units = Unit::all();
        $registeredHeads = User::where('is_head', true)->pluck('fullname', 'unit')->toArray();

        // Check if Head of Forensic has registered
        $forensicHead = User::where('is_head', true)
            ->where(function($q) {
                $q->where('unit', 'Forensic Directorate')
                  ->orWhere('unit', 'Forensic Head')
                  ->orWhere('unit', 'Directorate Head')
                  ->orWhere('unit', 'LIKE', '%Forensic Directorate%');
            })
            ->first();

        $hasForensicHead = !is_null($forensicHead);

        return view('auth.register', compact('units', 'registeredHeads', 'hasForensicHead', 'forensicHead'));
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
                  ->orWhere('unit', 'Directorate Head')
                  ->orWhere('unit', 'LIKE', '%Forensic Directorate%');
            })
            ->exists();

        if (!$forensicHeadExists) {
            // Force Head of Forensic registration parameters
            $request->merge([
                'unit' => 'Forensic Directorate',
            ]);
        } else {
            // Head of Forensic already exists. Prevent creating another Forensic Head account
            if ($request->input('unit') === 'Forensic Directorate') {
                throw ValidationException::withMessages([
                    'unit' => 'The Head of Forensic Directorate has already registered. Please select a specialized unit.',
                ]);
            }
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
            'service_id' => ['required', 'string', 'regex:/^(SD|JD)-[0-9]{3,}$/i', 'unique:users,service_id'],
            'phone_number' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
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

        if (!$forensicHeadExists) {
            $isStaff = false;
        } else {
            $roleInput = strtolower(trim($request->input('role', '')));
            if ($roleInput === 'head' || $roleInput === 'unit_head') {
                $isStaff = false;
            } elseif ($roleInput === 'staff' || $roleInput === 'officer') {
                $isStaff = true;
            } else {
                $isStaff = $request->has('is_staff') ? $request->boolean('is_staff') : true;
            }
        }
        $isHead = !$isStaff;
        // Unit Heads require Head of Forensic Directorate approval first before logging in
        $isApproved = !$forensicHeadExists ? true : ($isHead ? false : true);

        $inputUsername = trim($validated['username']);
        $userEmail = null;

        if (filter_var($inputUsername, FILTER_VALIDATE_EMAIL) || str_contains($inputUsername, '@')) {
            $userEmail = strtolower($inputUsername);
            if (User::where('email', $userEmail)->exists()) {
                throw ValidationException::withMessages([
                    'username' => 'The email address has already been registered.',
                ]);
            }
        }

        // Create officer user in database
        $user = User::create([
            'fullname' => $validated['fullname'],
            'username' => $inputUsername,
            'email' => $userEmail,
            'service_id' => $validated['service_id'],
            'phone_number' => $validated['phone_number'],
            'is_staff' => $isStaff,
            'is_head' => $isHead,
            'is_approved' => $isApproved,
            'unit' => $validated['unit'],
            'password' => Hash::make($validated['password']),
        ]);

        $statusMsg = !$forensicHeadExists
            ? 'Head of Forensic registration successful! Directorate command established. Unit Heads and Staff may now sign up.'
            : ($isHead
                ? 'Unit Head registration submitted! Your account has been forwarded to the Head of Forensic Directorate for approval first before you can log in.'
                : 'Registration successful! Please sign in with your Service ID or Username.');

        $redirect = redirect('/login')->with('status', $statusMsg);
        if ($isHead) {
            $redirect->with('pending_login_id', $validated['service_id']);
        }
        return $redirect;
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson() || $request->expectsJson() || $request->is('api/*') || str_contains($request->header('Accept', ''), 'application/json')) {
            return response()->json([
                'success' => true,
                'message' => 'Officer logged out successfully.'
            ], 200);
        }

        return redirect('/login')->with('status', 'Officer logged out successfully.');
    }

    /**
     * Approve and activate a Unit Head or Officer account.
     */
    public function approveUser($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->is_approved = true;
            $user->save();
            return response()->json(['success' => true, 'message' => 'Account approved and activated.']);
        }
        return response()->json(['success' => false, 'message' => 'User not found.'], 404);
    }

    /**
     * Deactivate a user account.
     */
    public function deactivateUser($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->is_approved = false;
            $user->save();
            return response()->json(['success' => true, 'message' => 'Account access deactivated.']);
        }
        return response()->json(['success' => false, 'message' => 'User not found.'], 404);
    }
}
