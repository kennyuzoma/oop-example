<?php

namespace App\Http\Middleware;
use Illuminate\Http\Response;
use Closure;

class CheckApiClientSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(is_null($api_client_secret = $request->header('X-Api-Client-Secret'))) {
            return response()->json([
                'message' => 'Client Secret required.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // does client secret exist?
        if(is_null($client_secret = \DB::table('oauth_clients')->where('secret', $api_client_secret)->first())) {
            return response()->json([
                'message' => 'Invalid Client Secret'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
