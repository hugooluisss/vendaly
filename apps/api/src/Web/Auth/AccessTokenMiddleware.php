<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Application\Auth\JwtService;
use App\Domain\Repository\UserRepositoryInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Http\Message\{ResponseFactoryInterface, StreamFactoryInterface};

final readonly class AccessTokenMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtService $jwt,
        private UserRepositoryInterface $users,
        private ResponseFactoryInterface $responses,
        private StreamFactoryInterface $streams,
    ) {}
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $request->getHeaderLine('Authorization');
        try {
            if (!str_starts_with($authorization, 'Bearer ')) {
                throw new \RuntimeException();
            }
            $userId = $this->jwt->verify(substr($authorization, 7));
        } catch (\Throwable) {
            return $this->unauthorized();
        }
        $user = $this->users->findById($userId);
        if ($user === null) {
            return $this->unauthorized();
        }
        return $handler->handle($request->withAttribute('user', $user)->withAttribute('user_id', $userId));
    }

    private function unauthorized(): ResponseInterface
    {
        return $this->responses
            ->createResponse(401)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streams->createStream('{"error":"Unauthorized"}'));
    }
}
