<?php

declare(strict_types=1);

namespace App\Web\Orders;

use App\Domain\Exception\ForbiddenException;
use App\Domain\Service\OrderService;
use DomainException;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamFactoryInterface};
use Yiisoft\Router\CurrentRoute;

final readonly class OrderController
{
    public function __construct(private OrderService $orders, private ResponseFactoryInterface $responses, private StreamFactoryInterface $streams, private CurrentRoute $route)
    {
    }

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $from = $this->date($request, 'from');
            $to = $this->date($request, 'to');
            $orders = $this->orders->listForBusiness((int) $request->getAttribute('user_id'), (int) $this->route->getArgument('businessId'), $from, $to);
            return $this->json(['orders' => array_map($this->map(...), $orders), 'count' => count($orders)]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], $e instanceof ForbiddenException ? 403 : 422);
        }
    }

    private function date(ServerRequestInterface $request, string $name): ?string
    {
        $value = $request->getQueryParams()[$name] ?? null;
        if ($value === null || $value === '') return null;
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1 || !checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))) {
            throw new DomainException("Invalid {$name} date.");
        }
        return $value;
    }

    private function map(array $entry): array
    {
        $order = $entry['order'];
        return ['id' => $order->id, 'created_at' => $order->createdAt, 'customer_note' => $order->customerNote, 'total' => $order->total, 'items' => array_map(static fn($item): array => ['name' => $item->productNameSnapshot, 'quantity' => $item->quantity, 'note' => $item->note], $entry['items'])];
    }

    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->responses->createResponse($status)->withHeader('Content-Type', 'application/json')->withBody($this->streams->createStream(json_encode($data, JSON_THROW_ON_ERROR)));
    }
}
