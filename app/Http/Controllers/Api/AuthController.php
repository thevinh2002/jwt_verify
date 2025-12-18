<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\WelcomeMail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Mail::to($user->email)->queue(new WelcomeMail($user));

        $user->sendEmailVerificationNotification();

        $verificationUrl = null;
        if (config('app.debug')) {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );
        }

        $token = Auth::login($user);

        return ApiResponse::success([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'verification_url' => $verificationUrl,
        ], 'Registered', 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! $token = Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! Auth::user()->hasVerifiedEmail()) {
            return ApiResponse::error('Email address is not verified.', 403, [
                'email' => ['Email address is not verified.'],
            ]);
        }

        return ApiResponse::success([
            'user' => Auth::user(),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 'Logged in');
    }

    public function me(Request $request)
    {
        return ApiResponse::success(Auth::user(), 'OK');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        return ApiResponse::success(null, 'Logged out');
    }

    public function refresh(Request $request)
    {
        return ApiResponse::success([
            'user' => Auth::user(),
            'token' => Auth::refresh(),
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 'Token refreshed');
    }

    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return ApiResponse::error('Invalid verification link.', 400);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(null, 'Email already verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return ApiResponse::success(null, 'Email verified');
    }

    public function resendVerification(Request $request)
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(null, 'Email already verified');
        }

        $user->sendEmailVerificationNotification();

        $verificationUrl = null;
        if (config('app.debug')) {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );
        }

        return ApiResponse::success([
            'verification_url' => $verificationUrl,
        ], 'Verification link sent');
    }
}
