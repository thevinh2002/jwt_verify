<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Support\ApiResponse;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::user();
            
            if (! $user || $user->role !== 'admin') {
                return ApiResponse::error('Access denied. Admin privileges required.', 403);
            }
            
            return $next($request);
        } catch (\Exception $e) {
            return ApiResponse::error('Invalid authentication', 401);
        }
    }
}
