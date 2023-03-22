<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Illuminate\Http\Request;

class JwtMiddleware extends BaseMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
          JWTAuth::parseToken()->authenticate();

        } catch (Exception $e) {

            if ($e instanceof TokenInvalidException){
                return response()->json(
                    ['success'=>false,
                      'message' => 'Token is Invalid',
                        'status'=>401],401);
            }else if ($e instanceof TokenExpiredException){
                return response()->json([
                    'success'=>false,
                    'message' => 'Token is Expired',
                    'status'=>103]);
            }else if ($e instanceof TokenBlacklistedException){
                return response()->json([
                    'success'=>false,
                    'message' => 'The token has been blacklisted',
                    'status'=>403
                    ]);
            }
            else{
                return response()->json([
                    'success'=>false,
                    'message' => 'Authorization Token not found',
                    'status'=>404
                    ]);
            }
        }
        return $next($request);
    }
}
