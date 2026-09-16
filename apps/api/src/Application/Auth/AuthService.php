<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Entity\{RefreshToken, User};
use App\Domain\Repository\{RefreshTokenRepositoryInterface, UserRepositoryInterface};
use InvalidArgumentException;
use RuntimeException;

use function bin2hex;
use function hash;
use function password_hash;
use function password_verify;
use function random_bytes;
use function strtolower;
use function trim;

use const PASSWORD_DEFAULT;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly JwtService $jwt,
    ) {}

    public function register(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            throw new InvalidArgumentException('Invalid email or password.');
        }
        if ($this->users->findByEmail($email) !== null) {
            throw new InvalidArgumentException('email is already in use.');
        }
        $user = new User();
        $user->email = $email;
        $user->passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $user->createdAt = date(DATE_ATOM);
        $this->users->create($user);
        return $this->tokens((int) $user->id);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail(strtolower(trim($email)));
        if ($user === null || !password_verify($password, $user->passwordHash)) {
            throw new RuntimeException('Invalid credentials.');
        }
        return $this->tokens((int) $user->id);
    }

    public function refresh(string $token): array
    {
        $record = $this->refreshTokens->findByHash(hash('sha256', $token));
        if ($record === null || $record->revokedAt !== null || strtotime($record->expiresAt) <= time()) {
            throw new RuntimeException('Invalid refresh token.');
        }
        $record->revokedAt = date(DATE_ATOM);
        $this->refreshTokens->create($record);
        return $this->tokens($record->userId);
    }

    private function tokens(int $userId): array
    {
        $plain = bin2hex(random_bytes(32));
        $record = new RefreshToken();
        $record->userId = $userId;
        $record->tokenHash = hash('sha256', $plain);
        $record->expiresAt = date(DATE_ATOM, time() + 2_592_000);
        $record->createdAt = date(DATE_ATOM);
        $this->refreshTokens->create($record);
        return [
            'access_token' => $this->jwt->issue($userId),
            'refresh_token' => $plain,
        ];
    }
}
