<?php

namespace App\Http\Middleware;

use App\Models\Token;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Support\ApiResponse;

class VerifyTokenInDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = JWTAuth::getToken();
            
            if (!$token) {
                return ApiResponse::error('Token not provided', 401);
            }

            // Kiểm tra token trong database
            $tokenRecord = Token::where('token', $token)->first();
            
            if (!$tokenRecord) {
                return ApiResponse::error('Token not found', 401);
            }

            // Kiểm tra token đã hết hạn chưa
            if ($tokenRecord->isExpired()) {
                // Xóa token đã hết hạn
                $tokenRecord->delete();
                return ApiResponse::error('Token expired', 401);
            }

            return $next($request);
            
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return ApiResponse::error('Token expired', 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return ApiResponse::error('Token invalid', 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return ApiResponse::error('Token absent', 401);
        }
    }
}
