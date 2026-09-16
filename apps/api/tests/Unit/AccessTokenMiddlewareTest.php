<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Auth\JwtService;
use App\Infrastructure\Cycle\Repository\CycleUserRepository;
use App\Web\Auth\AccessTokenMiddleware;
use HttpSoft\Message\{ResponseFactory, ServerRequest, StreamFactory};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

final class AccessTokenMiddlewareTest extends TestCase
{
    public function testMissingTokenIsUnauthorized(): void
    {
        self::assertSame(401, $this->middleware()->process(new ServerRequest(), $this->next())->getStatusCode());
    }
    public function testExpiredTokenIsUnauthorized(): void
    {
        $request = new ServerRequest([], [], [], [], null, 'GET', '/', ['Authorization' => 'Bearer ' . (new JwtService('secret', -1))->issue(1)]);
        self::assertSame(401, $this->middleware()->process($request, $this->next())->getStatusCode());
    }
    private function middleware(): AccessTokenMiddleware
    {
        return new AccessTokenMiddleware(new JwtService('secret'), new CycleUserRepository(), new ResponseFactory(), new StreamFactory());
    }
    private function next(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new ResponseFactory()->createResponse(204);
            }
        };
    }
}
