<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * If user is already logged in, redirect them to the dashboard.
     * No need to show login page to authenticated users.
     */
    public function showLoginForm()
    {
        // If already logged in, go straight to dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Otherwise, show the login page
        return view('auth.login');
    }

    /**
     * Handle the login form submission.
     *
     * Steps:
     * 1. Validate the input fields
     * 2. Attempt to log in with email + password
     * 3. Check if account is active
     * 4. Redirect to dashboard on success
     * 5. Return error on failure
     */
    public function login(Request $request)
    {
        // Step 1: Validate the form fields
        // 'required' means field cannot be empty
        // 'email' means must be a valid email format
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ], [
            // Custom error messages shown to the user
            'email.required'    => 'Please enter your email address.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Please enter your password.',
            'password.min'      => 'Password must be at least 6 characters.',
        ]);

        // Step 2: Get credentials from the request
        $credentials = $request->only('email', 'password');

        // 'remember' checkbox — keeps user logged in across browser sessions
        $remember = $request->boolean('remember');

        // Step 3: Attempt authentication using Laravel's Auth facade
        // Auth::attempt() checks email + password against the database
        // It automatically compares the plain password against the bcrypt hash
        if (Auth::attempt($credentials, $remember)) {

            // Step 4: Get the now-logged-in user
            $user = Auth::user();

            // Step 5: Check if account is active
            // Inactive users are blocked even if password is correct
            if (! $user->isActive()) {
                // Log them back out immediately
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the administrator.',
                ]);
            }

            // Step 6: Regenerate the session to prevent session fixation attacks
            // This is a security best practice in Laravel
            $request->session()->regenerate();

            // Step 7: Redirect to dashboard with a welcome message
            return redirect()->route('dashboard')
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        // Step 8: If Auth::attempt() returned false, credentials were wrong
        // withErrors() flashes the error to the session for display
        // 'email' key matches the input field name for proper display
        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->withInput($request->only('email'));
        // withInput() re-fills the email field so user doesn't retype it
    }

    /**
     * Log the user out of the application.
     *
     * Steps:
     * 1. Log out the user (clear auth session)
     * 2. Invalidate the session (destroy all session data)
     * 3. Regenerate CSRF token (security)
     * 4. Redirect to login page
     */
    public function logout(Request $request)
    {
        // Log the user out
        Auth::logout();

        // Invalidate the entire session
        $request->session()->invalidate();

        // Regenerate the CSRF token
        $request->session()->regenerateToken();

        // Redirect to login with a goodbye message
        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
