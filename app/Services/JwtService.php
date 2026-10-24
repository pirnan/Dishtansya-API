<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    protected string $secret;
    protected string $algorithm = 'HS256';

    public function __construct()
    {
        $key = config('app.jwt_secret') ?? config('app.key') ?? env('APP_KEY');
        if (is_string($key) && str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7));
            $this->secret = $decoded !== false ? $decoded : $key;
        } else {
            $this->secret = (string) ($key ?: 'default_jwt_secret_key_32_characters_long!');
        }
    }

    /**
     * Generate a signed JWT token for a given user.
     */
    public function generateToken(User $user, int $ttlSeconds = 86400): string
    {
        $issuedAt = time();
        $payload = [
            'iss' => config('app.url', 'http://localhost'),
            'sub' => $user->id,
            'email' => $user->email,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $ttlSeconds,
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Validate and decode a JWT token. Returns decoded payload or null if invalid/expired.
     */
    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algorithm));
        } catch (Exception $e) {
            return null;
        }
    }
}
