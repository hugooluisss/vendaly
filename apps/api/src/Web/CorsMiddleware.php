<?php

declare(strict_types=1);

namespace App\Web;

use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

final readonly class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responses,
        private string $allowedOrigins,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $allowed = in_array($origin, $this->origins(), true);
        $response = $request->getMethod() === 'OPTIONS'
            ? $this->responses->createResponse(204)
            : $handler->handle($request);

        return !$allowed ? $response : $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
            ->withHeader('Vary', 'Origin');
    }

    /** @return list<string> */
    private function origins(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->allowedOrigins))));
    }
}
