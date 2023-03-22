<?php

namespace App\Http\Middleware;

use App\Helper\Crypto;
use Closure;
use Illuminate\Http\Request;

class SecurityBeforeRequest
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $crypto = new Crypto();
        if ($request->hasHeader('franchise')) {
            $franchise = $request->header('franchise');
            $franchise = json_decode($franchise);
            $franchise = $crypto->decryp($franchise->data);
            $request->headers->set('franchise', $franchise->getdata()->message);
        }

        $requestData = json_decode($request->getContent(), 1);
        if ($requestData) {
            $requestA = $crypto->decryp($requestData['data']);

            if ($requestA->status() == 200) {
                $requestA = $requestA->getContent();
                $requestA = json_decode($requestA, 1);

                $request->merge(json_decode($requestA['message'], 1));

                $response = $next($request)->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
                ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token');
                $responseData = $response->getData();

                $crypto = new Crypto();

                $requestA = $crypto->encryp(json_encode($responseData));

                return response()->json([
            'success' => true,
            'data' => $requestA['message'],
            'status' => 200,
        ], 200);
            } else {
                $requestA = $requestA->getContent();

                $requestA = json_decode($requestA, 1);

                return response()->json([
                'success' => false,
                'message' => $requestA['message'],
                'status' => 400,
            ], 400);
            }
        } else {
            $response = $next($request);
            $responseData = $response->getData();
            if ($responseData->status == 200) {
                $crypto = new Crypto();

                $requestA = $crypto->encryp(json_encode($responseData));

                return response()->json([
                'success' => true,
                'data' => $requestA['message'],
                'status' => 200,
            ], 200);
            } else {
                $crypto = new Crypto();
                $requestA = $crypto->encryp(json_encode($responseData));

                return response()->json([
                'success' => false,
                'data' => $requestA['message'],
                'status' => 400,
            ], 400);
            }
        }
    }
}
