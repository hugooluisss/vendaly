<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Application\Auth\AuthService;
use InvalidArgumentException;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Message\{ResponseFactoryInterface, StreamFactoryInterface};

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class AuthController
{
    public function __construct(private AuthService $auth, private ResponseFactoryInterface $responses, private StreamFactoryInterface $streams) {}
    public function register(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        try {
            return $this->json($this->auth->register((string) ($body['email'] ?? ''), (string) ($body['password'] ?? '')), 201);
        } catch (InvalidArgumentException $e) {
            return $this->json(['errors' => ['email' => [$e->getMessage()]]], 422);
        }
    }
    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        try {
            return $this->json($this->auth->login((string) ($body['email'] ?? ''), (string) ($body['password'] ?? '')));
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid credentials.'], 401);
        }
    }
    public function refresh(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        try {
            return $this->json($this->auth->refresh((string) ($body['refresh_token'] ?? '')));
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid refresh token.'], 401);
        }
    }
    private function body(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);
        return is_array($data) ? $data : [];
    }
    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->responses->createResponse($status)->withHeader('Content-Type', 'application/json')->withBody($this->streams->createStream(json_encode($data, JSON_THROW_ON_ERROR)));
    }
}
