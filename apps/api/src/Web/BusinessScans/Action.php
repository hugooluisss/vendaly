<?php

declare(strict_types=1);

namespace App\Web\BusinessScans;

use App\Domain\Exception\ForbiddenException;
use App\Domain\Service\{BusinessMemberGuard, CatalogService};
use DateTimeImmutable;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamFactoryInterface};
use Yiisoft\Router\CurrentRoute;

final readonly class Action
{
    public function __construct(private CatalogService $service, private BusinessMemberGuard $guard, private ResponseFactoryInterface $responses, private StreamFactoryInterface $streams, private CurrentRoute $route) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $businessId = (int) $this->route->getArgument('id');
        try {
            $this->guard->assertOwner((int) $request->getAttribute('user_id'), $businessId);
        } catch (ForbiddenException) {
            return $this->responses->createResponse(403);
        }
        parse_str($request->getUri()->getQuery(), $query);
        try {
            $to = isset($query['to']) ? new DateTimeImmutable((string) $query['to']) : new DateTimeImmutable('today 23:59:59');
            $from = isset($query['from']) ? new DateTimeImmutable((string) $query['from']) : $to->modify('-29 days')->setTime(0, 0);
        } catch (\Throwable) {
            return $this->responses->createResponse(400);
        }
        if ($from > $to) {
            return $this->responses->createResponse(400);
        }
        $data = $this->service->scanStats($businessId, $from, $to);
        return $this->responses->createResponse()->withHeader('Content-Type', 'application/json')
            ->withBody($this->streams->createStream(json_encode($data, JSON_THROW_ON_ERROR)));
    }
}
