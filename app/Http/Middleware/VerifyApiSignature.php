<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyApiSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {

        $apiKey = $request->header('API-KEY');
        $timestamp = $request->header('TIMESTAMP');
        $signature = $request->header('SIGNATURE');

        if (!$apiKey || !$timestamp || !$signature) {
            return response()->json(['error' => 'Missing authentication headers'], 400);
        }

        // (Optional) Timestamp freshness check (e.g., allow only within 5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json(['error' => 'Invalid or expired timestamp'], 401);
        }

        $apiArr = config('app.api');
        $found = false;
        foreach ($apiArr as $api) {
            if ($api['key'] === $apiKey) {
                $found = true;
                $config_secretkey = $api['secret'];
                break;
            }
        }
        if (!$found) {
            return response()->json(['error' => 'Invalid API key'], 403);
        }

        $data = $timestamp . $apiKey;
        $expectedSignature = hash_hmac('sha256', $data, $config_secretkey);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
