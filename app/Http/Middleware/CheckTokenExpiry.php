<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $createdAt = $user->currentAccessToken()->created_at;
            $diffInMinutes = $createdAt->diffInMinutes(now());

            if ($diffInMinutes > 60) {
                $user->currentAccessToken()->delete();

                return response()->json([
                    'message' => 'Token expired. Please login again.'
                ], 401);
            }
        }

        return $next($request);
    }
}