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
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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
            'username' => $validated['name'], // Use name as username
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

        $token = JWTAuth::fromUser($user);

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
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Check if login is email or username
        $loginField = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        $credentials = [
            $loginField => $validated['login'],
            'password' => $validated['password'],
        ];

        if (! $token = JWTAuth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Get user after successful authentication
        $user = JWTAuth::user();
        
        if (! $user->hasVerifiedEmail()) {
            return ApiResponse::error('Email address is not verified.', 403, [
                'email' => ['Email address is not verified.'],
            ]);
        }

        return ApiResponse::success([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 'Logged in');
    }

    public function me(Request $request)
    {
        try {
            $user = JWTAuth::user();
            return ApiResponse::success($user, 'OK');
        } catch (\Exception $e) {
            return ApiResponse::error('Invalid token', 401);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::logout();
        } catch (\Exception $e) {
            // Token invalid or expired, just ignore
        }

        return ApiResponse::success(null, 'Logged out');
    }

    public function refresh(Request $request)
    {
        try {
            $user = JWTAuth::user();
            $token = JWTAuth::refresh();
            
            return ApiResponse::success([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ], 'Token refreshed');
        } catch (\Exception $e) {
            return ApiResponse::error('Unable to refresh token', 401);
        }
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $user = JWTAuth::user();
            
            // Verify current password
            if (! Hash::check($validated['current_password'], $user->password)) {
                return ApiResponse::error('Current password is incorrect', 400, [
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }

            // Update password
            $user->password = Hash::make($validated['password']);
            $user->save();

            // Logout user after password change (invalidate all tokens)
            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (\Exception $e) {
                // Token invalidation failed, but password was changed
            }

            return ApiResponse::success([
                'message' => 'Password changed successfully. You have been logged out for security.',
                'auto_logout' => true
            ], 'Password changed successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Unable to change password', 401);
        }
    }

    public function adminDashboard()
    {
        try {
            $user = JWTAuth::user();
            
            $stats = [
                'total_users' => User::count(),
                'verified_users' => User::whereNotNull('email_verified_at')->count(),
                'admin_users' => User::where('role', 'admin')->count(),
                'recent_users' => User::orderBy('created_at', 'desc')->take(5)->get(['id', 'name', 'email', 'role', 'created_at']),
            ];
            
            return ApiResponse::success([
                'admin_user' => $user,
                'stats' => $stats
            ], 'Admin dashboard data');
        } catch (\Exception $e) {
            return ApiResponse::error('Unable to fetch admin data', 401);
        }
    }

    public function adminUsers()
    {
        try {
            $users = User::select('id', 'name', 'email', 'role', 'email_verified_at', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return ApiResponse::success([
                'users' => $users
            ], 'All users data');
        } catch (\Exception $e) {
            return ApiResponse::error('Unable to fetch users', 401);
        }
    }

    public function adminCreateUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:user,admin'],
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'email_verified_at' => now(), // Auto-verify admin-created users
            ]);

            return ApiResponse::success([
                'user' => $user
            ], 'User created successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Unable to create user', 500);
        }
    }

    public function adminUpdateUser(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'role' => ['sometimes', 'string', 'in:user,admin'],
            'email_verified_at' => ['sometimes', 'nullable', 'date'],
        ]);

        try {
            $user = User::findOrFail($id);
            
            // Prevent admin from changing their own role to user (security)
            $currentUser = JWTAuth::user();
            if ($currentUser->id == $user->id && isset($validated['role']) && $validated['role'] !== 'admin') {
                return ApiResponse::error('You cannot change your own admin role', 403);
            }

            $user->update($validated);

            return ApiResponse::success([
                'user' => $user
            ], 'User updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Unable to update user', 500);
        }
    }

    public function adminDeleteUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent admin from deleting themselves
            $currentUser = JWTAuth::user();
            if ($currentUser->id == $user->id) {
                return ApiResponse::error('You cannot delete your own account', 403);
            }

            $user->delete();

            return ApiResponse::success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Unable to delete user', 500);
        }
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

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Find user by email
        $user = User::where('email', $validated['email'])->first();
        
        if (! $user) {
            // Don't reveal if user exists or not for security
            return ApiResponse::success([
                'message' => 'Password reset link sent to your email',
                'email' => $validated['email']
            ], 'Password reset link sent to your email');
        }

        // Generate custom reset token
        $token = Str::random(60);
        $hashedToken = Hash::make($token);
        
        // Store token in password_resets table
        \DB::table('password_resets')->insert([
            'email' => $validated['email'],
            'token' => $hashedToken,
            'created_at' => now()
        ]);

        // Generate reset URL
        $resetUrl = URL::route('password.reset', ['token' => $token, 'email' => $validated['email']]);

        // Try to send email (will be logged if mail driver is 'log')
        try {
            Mail::raw("Hello {$user->name},\n\nClick here to reset your password: {$resetUrl}\n\nThis link will expire in 24 hours.", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Password Reset Request');
            });
        } catch (\Exception $e) {
            // Log error but continue (debug mode will provide URL anyway)
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
        }

        $response = [
            'message' => 'Password reset link sent to your email',
            'email' => $validated['email']
        ];
        
        // Add debug info in development
        if (config('app.debug')) {
            $response['debug_reset_url'] = $resetUrl;
            $response['debug_token'] = $token;
            $response['mail_sent'] = 'Check Laravel logs for email content';
        }
        
        return ApiResponse::success($response, 'Password reset link sent to your email');
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Find user by email
        $user = User::where('email', $validated['email'])->first();
        
        if (! $user) {
            return ApiResponse::error('User not found', 404);
        }

        // Get all reset tokens for this email
        $resetTokens = \DB::table('password_resets')
            ->where('email', $validated['email'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Verify the reset token
        $validToken = null;
        foreach ($resetTokens as $resetToken) {
            if (Hash::check($validated['token'], $resetToken->token)) {
                // Check if token is not expired (24 hours)
                $createdAt = \Carbon\Carbon::parse($resetToken->created_at);
                if ($createdAt->diffInHours(now()) < 24) {
                    $validToken = $resetToken;
                    break;
                }
            }
        }

        if (! $validToken) {
            return ApiResponse::error('Invalid or expired reset token', 400);
        }

        // Reset the password
        $user->password = Hash::make($validated['password']);
        $user->save();

        // Delete all reset tokens for this email
        \DB::table('password_resets')
            ->where('email', $validated['email'])
            ->delete();

        return ApiResponse::success(null, 'Password reset successfully');
    }

    public function resendVerification(Request $request)
    {
        try {
            $user = JWTAuth::user();

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
        } catch (\Exception $e) {
            return ApiResponse::error('Invalid token', 401);
        }
    }
}
