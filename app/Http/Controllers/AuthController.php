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
            Auth::login($user, $request->has('remember'));
            $request->session()->regenerate();

            return redirect()->intended('/cases')->with('status', 'Login Successful! Welcome to NACOC Master Case Directory.');
        }

        return back()->withErrors([
            'login_id' => 'Wrong username or password.',
        ])->onlyInput('login_id');
    }

    /**
     * Show the registration form with dynamic units from database.
     */
    public function showRegister()
    {
        $units = Unit::all();
        $registeredHeads = User::where('is_head', true)->pluck('fullname', 'unit')->toArray();

        return view('auth.register', compact('units', 'registeredHeads'));
    }

    /**
     * Handle officer registration and save to PostgreSQL database.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'fullname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'service_id' => ['required', 'string', 'max:50', 'unique:users,service_id'],
            'phone_number' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'unit' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'username.unique' => 'The username has already been taken.',
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

        $isStaff = $request->has('is_staff');

        // Create new officer user in PostgreSQL
        $user = User::create([
            'fullname' => $validated['fullname'],
            'username' => $validated['username'],
            'service_id' => $validated['service_id'],
            'phone_number' => $validated['phone_number'],
            'is_staff' => $isStaff,
            'is_head' => !$isStaff,
            'unit' => $validated['unit'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect('/login')->with('status', 'Registration successful! Please sign in with your Service ID or Username.');
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
}
