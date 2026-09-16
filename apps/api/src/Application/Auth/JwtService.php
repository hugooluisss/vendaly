<?php

declare(strict_types=1);

namespace App\Application\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;

final class JwtService
{
    public function __construct(
        private readonly string $secret,
        private readonly int $accessLifetime = 900,
    ) {}

    public function issue(int $userId): string
    {
        return JWT::encode(
            ['user_id' => $userId, 'exp' => time() + $this->accessLifetime],
            $this->secret,
            'HS256',
        );
    }

    public function verify(string $token): int
    {
        try {
            $claims = JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Throwable $exception) {
            throw new RuntimeException('Invalid access token.', 0, $exception);
        }

        if (!isset($claims->user_id)) {
            throw new RuntimeException('Invalid access token.');
        }

        return (int) $claims->user_id;
    }
}
