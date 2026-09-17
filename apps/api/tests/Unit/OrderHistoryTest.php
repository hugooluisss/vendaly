<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Entity\{Business, BusinessHours, BusinessMember, Order, OrderItem};
use App\Domain\Exception\ForbiddenException;
use App\Domain\Repository\{BusinessManagementRepositoryInterface, BusinessRepositoryInterface, OrderRepositoryInterface, ProductRepositoryInterface};
use App\Domain\Service\{BusinessMemberGuard, OrderService};
use HttpSoft\Message\{ResponseFactory, ServerRequest, StreamFactory};
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\{CurrentRoute, Route};

final class OrderHistoryTest extends TestCase
{
    public function testNonMemberCannotListOrders(): void
    {
        $this->expectException(ForbiddenException::class);
        (new OrderService($this->createMock(BusinessRepositoryInterface::class), $this->createMock(ProductRepositoryInterface::class), new HistoryOrders(), new BusinessMemberGuard(new HistoryBusinesses())))->listForBusiness(9, 1);
    }

    public function testEndpointReturnsFilteredShapeAndExactCount(): void
    {
        $order = new Order(); $order->id = 4; $order->createdAt = '2026-09-10T12:00:00+00:00'; $order->total = '25.00';
        $item = new OrderItem(); $item->productNameSnapshot = 'Burger'; $item->quantity = 2; $item->note = 'No onions';
        $repository = new HistoryOrders([['order' => $order, 'items' => [$item]]]);
        $businesses = new HistoryBusinesses(); $member = new BusinessMember(); $member->businessId = 1; $member->userId = 7; $businesses->member = $member;
        $route = new CurrentRoute(); $route->setRouteWithArguments(Route::get('/businesses/{businessId}/orders'), ['businessId' => '1']);
        $request = (new ServerRequest(queryParams: ['from' => '2026-09-01', 'to' => '2026-09-30']))->withAttribute('user_id', 7);
        $response = (new \App\Web\Orders\OrderController(new OrderService($this->createMock(BusinessRepositoryInterface::class), $this->createMock(ProductRepositoryInterface::class), $repository, new BusinessMemberGuard($businesses)), new ResponseFactory(), new StreamFactory(), $route))->list($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['orders' => [['id' => 4, 'created_at' => '2026-09-10T12:00:00+00:00', 'customer_note' => null, 'total' => '25.00', 'items' => [['name' => 'Burger', 'quantity' => 2, 'note' => 'No onions']]]], 'count' => 1], json_decode((string) $response->getBody(), true));
    }
}

final class HistoryBusinesses implements BusinessManagementRepositoryInterface
{
    public ?BusinessMember $member = null;
    public function create(Business $entity): Business { return $entity; }
    public function findById(int $id): ?Business { return null; }
    public function findPublishedBySlug(string $slug): ?Business { return null; }
    public function findPublishedDirectory(?string $category, ?string $location, ?float $latitude = null, ?float $longitude = null, ?string $name = null): array { return []; }
    public function findHours(int $businessId): array { return []; }
    public function findByOwnerUserId(int $userId): ?Business { return null; }
    public function findByMemberUserId(int $userId): ?Business { return null; }
    public function findBySlug(string $slug): ?Business { return null; }
    public function update(Business $business): Business { return $business; }
    public function createMember(BusinessMember $member): BusinessMember { return $member; }
    public function findMember(int $businessId, int $userId): ?BusinessMember { return $this->member?->businessId === $businessId && $this->member->userId === $userId ? $this->member : null; }
    public function saveHours(BusinessHours $hours): BusinessHours { return $hours; }
}

final class HistoryOrders implements OrderRepositoryInterface
{
    public function __construct(private array $items = []) {}
    public function create(\App\Domain\Entity\Order $entity): Order { return $entity; }
    public function createWithItems(Order $entity, array $items): Order { return $entity; }
    public function createItem(OrderItem $entity): OrderItem { return $entity; }
    public function findById(int $id): ?Order { return null; }
    public function findByBusinessId(int $businessId, ?string $from = null, ?string $to = null): array { return $this->items; }
    public function findItems(int $orderId): array { return []; }
}
