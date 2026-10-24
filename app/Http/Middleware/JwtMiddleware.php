<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $payload = $this->jwtService->validateToken($token);

        if (! $payload || ! isset($payload->sub)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = User::find($payload->sub);

        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Set authenticated user on the request and auth guard
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        auth()->setUser($user);

        return $next($request);
    }
}
