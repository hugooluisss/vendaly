<?php

declare(strict_types=1);

namespace App\Web\PublicCatalogScan;

use App\Domain\Service\CatalogService;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Yiisoft\Router\CurrentRoute;

final readonly class Action
{
    public function __construct(private CatalogService $service, private ResponseFactoryInterface $responses, private CurrentRoute $route) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responses->createResponse($this->service->recordScan((string) $this->route->getArgument('slug')) ? 204 : 404);
    }
}
