<?php

declare(strict_types=1);

namespace App\Web\BusinessQr;

use App\Domain\Service\QrCodeService;
use DomainException;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamFactoryInterface};
use Yiisoft\Router\CurrentRoute;

final readonly class Action
{
    public function __construct(
        private QrCodeService $service,
        private ResponseFactoryInterface $responses,
        private StreamFactoryInterface $streams,
        private CurrentRoute $route,
    ) {
    }
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!is_int($userId) && !ctype_digit((string) $userId)) {
            return $this->responses->createResponse(401);
        }
        try {
            return $this->responses->createResponse()
                ->withHeader('Content-Type', 'image/svg+xml')
                ->withBody($this->streams->createStream($this->service->image(
                    (int) $this->route->getArgument('id'),
                    (int) $userId,
                )));
        } catch (DomainException) {
            return $this->responses->createResponse(404);
        }
    }
}
