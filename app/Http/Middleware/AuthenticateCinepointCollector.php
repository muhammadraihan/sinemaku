<?php

namespace App\Http\Middleware;

use Closure;

class AuthenticateCinepointCollector
{
    public function handle($request, Closure $next)
    {
        if (app()->environment('production') && !$request->isSecure()) {
            return response()->json(['message' => 'Secure transport is required.'], 403);
        }

        if ($request->getQueryString() !== null || !$request->isJson()) return response()->json(['message'=>'JSON without query parameters required.'],422);
        $secret = (string) config('services.cinepoint_ingest.secret', '');
        $keyId = (string) $request->header('X-Cinepoint-Key-Id', '');
        $timestamp = (string) $request->header('X-Cinepoint-Timestamp', '');
        $delivery = (string) $request->header('X-Cinepoint-Delivery', '');
        $bodyHash = (string) $request->header('X-Cinepoint-Body-Sha256', '');
        $signature = (string) $request->header('X-Cinepoint-Signature', '');
        $body = $request->getContent();

        if (strlen($body) > 2097152) return response()->json(['message' => 'Payload too large.'], 413);
        if (strlen($secret) < 32 || !hash_equals((string) config('services.cinepoint_ingest.key_id'), $keyId)) return response()->json(['message' => 'Invalid collector authentication.'], 401);
        if (!preg_match('/^\d{10}$/D', $timestamp) || abs(time() - (int) $timestamp) > 300 ||
            !preg_match('/^[a-f0-9]{32}$/D', $delivery) || !preg_match('/^[a-f0-9]{64}$/D', $bodyHash) ||
            !preg_match('/^[a-f0-9]{64}$/D', $signature) || !hash_equals(hash('sha256', $body), $bodyHash)) {
            return response()->json(['message' => 'Invalid collector authentication.'], 401);
        }

        $canonical = $request->method()."\n/".$request->path()."\n".$timestamp."\n".$delivery."\n".$bodyHash;
        if (!hash_equals(hash_hmac('sha256', $canonical, $secret), $signature)) {
            return response()->json(['message' => 'Invalid collector authentication.'], 401);
        }

        return $next($request);
    }
}
