<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class ApiProtectedRoute extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->exists('Authorization')) {
            $request->headers->set('Authorization', $request->get('Authorization'));
        }
        try {
            JWTAuth::parseToken()->authenticate();
            
        } catch (\Exception $e) {
            if ($e instanceof TokenInvalidException) {
                $ret = response()->json(['status' => 'Token is Invalid'], 401);
            } elseif ($e instanceof TokenExpiredException) {
                $ret = response()->json(['status' => 'Token is Expired'], 401);
            } else {
                $ret = response()->json(['status' => 'Authorization Token not found'], 401);
            }
            return $ret;
        }
        return $next($request);
    }
}
