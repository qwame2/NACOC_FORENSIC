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
     * Show the login form.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt against PostgreSQL database.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginId = trim($credentials['login_id']);
        $password = $credentials['password'];

        // Find user by service_id, username, or email
        $user = User::where('service_id', $loginId)
            ->orWhere('username', $loginId)
            ->orWhere('email', $loginId)
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            $isForensicHead = $user->is_head && (
                $user->unit === 'Forensic Directorate' ||
                $user->unit === 'Forensic Head' ||
                $user->unit === 'Directorate Head' ||
                str_contains($user->unit, 'Forensic Directorate')
            );

            // Block Unit Head login if account is not approved by Head of Forensic Directorate
            if ($user->is_head && !$isForensicHead && isset($user->is_approved) && !$user->is_approved) {
                return back()->withErrors([
                    'login_id' => 'Account Pending Approval: Your Unit Head registration has been submitted to the Head of Forensic Directorate for approval. You cannot log in until your account is approved.',
                ])->with('pending_login_id', $loginId)->onlyInput('login_id');
            }

            Auth::login($user, $request->has('remember'));
            $request->session()->regenerate();

            if ($isForensicHead) {
                return redirect()->intended('/Forensic_Head')->with('status', 'Login Successful! Welcome to NACOC Forensic Directorate Command Portal.');
            }

            if ($user->is_head) {
                $unitTitle = $user->unit ?? 'Unit';
                return redirect()->intended('/Crime_Scene_Support/head')->with('status', "Login Successful! Welcome to {$unitTitle} Command Portal.");
            }

            if ($user->is_staff) {
                return redirect()->intended('/Crime_Scene_Support/staff')->with('status', 'Login Successful! Welcome to Field Operations Portal.');
            }

            return redirect()->intended('/cases')->with('status', 'Login Successful! Welcome to NACOC Master Case Directory.');
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

        $isStaff = !$forensicHeadExists ? false : $request->has('is_staff');
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
