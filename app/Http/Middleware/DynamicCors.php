<?php

namespace App\Http\Middleware;

use Closure;

class DynamicCors
{
    public function handle($request, Closure $next)
    {
        $allowedOrigins = [
            'http://localhost:8080',
            'http://localhost:30000',
            // Add other allowed origins here
        ];

        $origin = $request->header('Origin');

        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Allow-Credentials: false');
        }

        if ($request->getMethod() == "OPTIONS") {
            return response('', 200);
        }

        return $next($request);
    }
}
