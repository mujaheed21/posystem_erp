<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate the user and create a session.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Use the 'web' guard explicitly to ensure session cookies are issued
        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Regenerate session to prevent session fixation attacks
        $request->session()->regenerate();

        return response()->json([
            'user' => Auth::user(),
            'message' => 'Authenticated'
        ]);
    }

    /**
     * Log the user out and destroy the session.
     */
    public function logout(Request $request)
    {
        // 1. Log the user out of the web guard
        Auth::guard('web')->logout();

        // 2. Invalidate the existing session
        $request->session()->invalidate();

        // 3. Regenerate the CSRF token for the next session
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }
}