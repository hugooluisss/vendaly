<?php

declare(strict_types=1);

namespace App\Web\PublicCatalog;

use App\Domain\Service\CatalogService;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamFactoryInterface};
use Yiisoft\Router\CurrentRoute;

final readonly class Action
{
    public function __construct(
        private CatalogService $service,
        private ResponseFactoryInterface $responses,
        private StreamFactoryInterface $streams,
        private CurrentRoute $route,
    ) {}
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->service->publicCatalog((string) $this->route->getArgument('slug'));
        if ($data === null) {
            return $this->responses->createResponse(404);
        }
        return $this->responses->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streams->createStream((string) json_encode($data)));
    }
}
