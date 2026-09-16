<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Entity\{Business, Category, Order, OrderItem, Product};
use App\Domain\Repository\{BusinessRepositoryInterface, CategoryRepositoryInterface, OrderRepositoryInterface, ProductRepositoryInterface};
use App\Domain\Service\{CatalogService, OrderService, QrCodeService, WhatsAppOrderLink};
use DomainException;
use PHPUnit\Framework\TestCase;

final class PublicCatalogAndOrdersTest extends TestCase
{
    public function testPublishedCatalogOnlyReturnsActiveProducts(): void
    {
        $b = $this->business(true);
        $c = new Category();
        $c->id = 4;
        $c->businessId = 1;
        $c->name = 'Food';
        $active = $this->product(10, true, 'Active');
        $inactive = $this->product(11, false, 'Inactive');
        $ingredients = new CatalogIngredients([10 => ['Tomato']]);
        $result = (new CatalogService(new FakeBusinesses($b), new FakeCategories([$c]), new FakeProducts([$active, $inactive]), $ingredients))->publicCatalog('shop');
        self::assertCount(1, $result['categories'][0]['products']);
        self::assertSame('Active', $result['categories'][0]['products'][0]['name']);
        self::assertSame(['Tomato'], $result['categories'][0]['products'][0]['ingredients']);
    }

    public function testSoftDeletedProductCannotCreateAnOrder(): void
    {
        $product = $this->product(10, true, 'Deleted');
        $product->deletedAt = date(DATE_ATOM);
        $service = new OrderService(new FakeBusinesses($this->business(true)), new FakeProducts([$product]), new FakeOrders());
        $this->expectException(DomainException::class);
        $service->create('shop', ['items' => [['product_id' => 10, 'quantity' => 1]]]);
    }

    public function testUnknownAndUnpublishedCatalogsReturnNothing(): void
    {
        foreach ([$this->business(false), null] as $business) {
            self::assertNull((new CatalogService(new FakeBusinesses($business), new FakeCategories(), new FakeProducts(), new CatalogIngredients()))->publicCatalog('shop'));
        }
    }

    public function testPublicCatalogIncludesEmptyIngredientsAndBatchesOnce(): void
    {
        $b = $this->business(true); $c = new Category(); $c->id = 4; $c->businessId = 1;
        $ingredients = new CatalogIngredients([10 => ['Tomato']]);
        $result = (new CatalogService(new FakeBusinesses($b), new FakeCategories([$c]), new FakeProducts([$this->product(10, true, 'A'), $this->product(11, true, 'B')]), $ingredients))->publicCatalog('shop');
        self::assertSame(['Tomato'], $result['categories'][0]['products'][0]['ingredients']);
        self::assertSame([], $result['categories'][0]['products'][1]['ingredients']);
        self::assertSame(1, $ingredients->loads);
    }

    public function testOrderSnapshotsMixedPricesAndRejectsEmptyAndUnpublished(): void
    {
        $b = $this->business(true);
        $priced = $this->product(10, true, 'Changed later', '12.50');
        $free = $this->product(11, true, 'Service');
        $orders = new FakeOrders();
        $products = new FakeProducts([$priced, $free]);
        $service = new OrderService(new FakeBusinesses($b), $products, $orders);
        $saved = $service->create('shop', ['items' => [['product_id' => 10, 'quantity' => 2], ['product_id' => 11, 'quantity' => 1, 'note' => 'No onions']]]);
        self::assertSame('25.00', $saved['order']->total);
        self::assertSame('Changed later', $saved['items'][0]->productNameSnapshot);
        self::assertNull($saved['items'][1]->unitPriceSnapshot);
        self::assertSame(1, $products->bulkLoads);
        try {
            $service->create('shop', ['items' => []]);
            self::fail();
        } catch (DomainException) {
            self::assertCount(1, $orders->orders);
        }
        try {
            (new OrderService(new FakeBusinesses($this->business(false)), new FakeProducts([$priced]), new FakeOrders()))->create('shop', ['items' => [['product_id' => 10, 'quantity' => 1]]]);
            self::fail();
        } catch (DomainException) {
            self::assertTrue(true);
        }
    }

    public function testPersistenceFailureRollsBackOrderAndAllItems(): void
    {
        $orders = new FakeOrders(true);
        try {
            (new OrderService(new FakeBusinesses($this->business(true)), new FakeProducts([$this->product(10, true, 'Burger')]), $orders))
                ->create('shop', ['items' => [['product_id' => 10, 'quantity' => 1]]]);
            self::fail('Expected persistence failure.');
        } catch (\RuntimeException) {
            self::assertCount(0, $orders->orders);
            self::assertCount(0, $orders->items);
        }
    }

    public function testWhatsAppLinkMixedItemsAndMissingNumber(): void
    {
        $order = new Order();
        $order->total = '25.00';
        $priced = new OrderItem();
        $priced->quantity = 2;
        $priced->productNameSnapshot = 'Burger';
        $priced->unitPriceSnapshot = '12.50';
        $free = new OrderItem();
        $free->quantity = 1;
        $free->productNameSnapshot = 'Service';
        $free->note = 'No onions';
        $link = WhatsAppOrderLink::generate($order, [$priced, $free], '+52 (55) 1234-5678');
        self::assertStringContainsString('wa.me/525512345678?text=', $link);
        self::assertStringContainsString(rawurlencode("2x Burger — 12.50\n1x Service (No onions)\n\nTotal: 25.00"), $link);
        $this->expectException(DomainException::class);
        WhatsAppOrderLink::generate($order, [$free], null);
    }

    public function testQrContainsTheExactPublicCatalogUrl(): void
    {
        putenv('PUBLIC_CATALOG_BASE_URL=https://vendaly.test/public/catalog');
        $svg = (new QrCodeService(new FakeBusinesses($this->business(true))))->image(1, 0);
        $expected = (new \Endroid\QrCode\Builder\Builder(writer: new \Endroid\QrCode\Writer\SvgWriter(), data: 'https://vendaly.test/public/catalog/shop'))->build()->getString();
        self::assertSame($expected, $svg);
    }

    private function business(bool $published): Business
    {
        $b = new Business();
        $b->id = 1;
        $b->name = 'Shop';
        $b->slug = 'shop';
        $b->isPublished = $published;
        $b->whatsappNumber = '+525512345678';
        return $b;
    }
    private function product(int $id, bool $active, string $name, ?string $price = null): Product
    {
        $p = new Product();
        $p->id = $id;
        $p->businessId = 1;
        $p->categoryId = 4;
        $p->name = $name;
        $p->price = $price;
        $p->isActive = $active;
        return $p;
    }
}

final class FakeBusinesses implements BusinessRepositoryInterface
{
    public function __construct(private ?Business $business) {} public function create(Business $entity): Business
    {
        return $entity;
    } public function findById(int $id): ?Business
    {
        return $this->business;
    } public function findPublishedBySlug(string $slug): ?Business
    {
        return $this->business?->isPublished ? $this->business : null;
    } public function findHours(int $businessId): array
    {
        return [];
    }
}
final class FakeCategories implements CategoryRepositoryInterface
{
    public function __construct(private array $items = []) {} public function create(Category $entity): Category
    {
        return $entity;
    } public function findById(int $id): ?Category
    {
        return null;
    } public function findByBusinessId(int $businessId): array
    {
        return $this->items;
    }
}
final class FakeProducts implements ProductRepositoryInterface
{
    public int $bulkLoads = 0;
    public function __construct(private array $items = []) {} public function create(Product $entity): Product
    {
        return $entity;
    } public function findById(int $id): ?Product
    {
        return null;
    } public function findActiveByBusinessId(int $businessId): array
    {
        return array_values(array_filter($this->items, static fn(Product $p) => $p->isActive && $p->deletedAt === null));
    } public function findActiveByIdsForBusiness(array $ids, int $businessId): array
    {
        $this->bulkLoads++;
        return array_values(array_filter($this->items, static fn(Product $p) => in_array($p->id, $ids, true) && $p->isActive && $p->deletedAt === null));
    }
}
final class FakeOrders implements OrderRepositoryInterface
{
    public array $orders = [];
    public array $items = [];
    public function __construct(private bool $fail = false) {} public function create(Order $entity): Order
    {
        $entity->id = count($this->orders) + 1;
        $this->orders[] = $entity;
        return $entity;
    } public function createWithItems(Order $entity, array $items): Order
    {
        $entity->id = 1;
        $this->orders[] = $entity;
        foreach ($items as $index => $item) {
            if ($this->fail && $index === 0) {
                $this->orders = [];
                $this->items = [];
                throw new \RuntimeException('forced failure');
            } $this->items[] = $item;
        } return $entity;
    } public function createItem(OrderItem $entity): OrderItem
    {
        $this->items[] = $entity;
        return $entity;
    } public function findById(int $id): ?Order
    {
        return null;
    } public function findItems(int $orderId): array
    {
        return $this->items;
    }
}
final class CatalogIngredients implements \App\Domain\Repository\ProductIngredientRepositoryInterface
{
    public int $loads = 0;
    public function __construct(private array $items = []) {}
    public function replaceForProduct(int $productId, array $names): void { }
    public function findByProductIds(array $productIds): array { $this->loads++; return $this->items; }
}
