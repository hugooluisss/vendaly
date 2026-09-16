<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Auth\{AuthService, JwtService};
use App\Infrastructure\Cycle\Repository\{CycleRefreshTokenRepository, CycleUserRepository};
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    private AuthService $auth;
    protected function setUp(): void
    {
        $this->auth = new AuthService(new CycleUserRepository(), new CycleRefreshTokenRepository(), new JwtService('test-secret'));
    }
    public function testRegistrationAndDuplicateEmail(): void
    {
        $email = 'auth-' . uniqid('', true) . '@example.test';
        $tokens = $this->auth->register($email, 'password-123');
        self::assertArrayHasKey('access_token', $tokens);
        self::assertArrayHasKey('refresh_token', $tokens);
        $this->expectException(\InvalidArgumentException::class);
        $this->auth->register($email, 'password-123');
    }
    public function testLoginAndInvalidCredentials(): void
    {
        $email = 'login-' . uniqid('', true) . '@example.test';
        $this->auth->register($email, 'password-123');
        self::assertArrayHasKey('access_token', $this->auth->login($email, 'password-123'));
        $this->expectException(\RuntimeException::class);
        $this->auth->login($email, 'wrong-password');
    }
    public function testRefreshRotatesAndRejectsOldToken(): void
    {
        $tokens = $this->auth->register('refresh-' . uniqid('', true) . '@example.test', 'password-123');
        $rotated = $this->auth->refresh($tokens['refresh_token']);
        self::assertNotSame($tokens['refresh_token'], $rotated['refresh_token']);
        $this->expectException(\RuntimeException::class);
        $this->auth->refresh($tokens['refresh_token']);
    }
    public function testJwtRejectsExpiredToken(): void
    {
        $jwt = new JwtService('test-secret', -1);
        $this->expectException(\RuntimeException::class);
        $jwt->verify($jwt->issue(1));
    }
}
