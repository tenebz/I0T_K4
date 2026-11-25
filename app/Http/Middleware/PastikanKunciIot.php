<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bila IOT_API_KEY diisi, alat IoT wajib mengirim header X-Api-Key yang sama. */
class PastikanKunciIot
{
    public function handle(Request $request, Closure $next): Response
    {
        $kunci = config('kualitas_air.api_key');

        if (filled($kunci) && ! hash_equals((string) $kunci, (string) $request->header('X-Api-Key'))) {
            return response()->json(['message' => 'Invalid device API key.'], 401);
        }

        return $next($request);
    }
}
