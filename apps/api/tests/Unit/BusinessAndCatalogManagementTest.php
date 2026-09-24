<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Entity\{Business, BusinessHours, BusinessMember, Category, OrderStatus, PaymentMethod, Product, ProductImage, ProductOption};
use App\Domain\Exception\ForbiddenException;
use App\Domain\Repository\{BusinessManagementRepositoryInterface, CategoryManagementRepositoryInterface, ObjectStorageInterface, OrderStatusRepositoryInterface, PaymentMethodRepositoryInterface, ProductManagementRepositoryInterface, ProductOptionRepositoryInterface};
use App\Domain\Service\{BusinessMemberGuard, BusinessService, CategoryService, CatalogService, ProductService};
use DomainException;
use HttpSoft\Message\{ResponseFactory, ServerRequest, StreamFactory};
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\{CurrentRoute, Route};

final class BusinessAndCatalogManagementTest extends TestCase
{
    public function testBusinessCreationCollisionAndSingleBusinessRule(): void
    {
        $repo = new ManagementBusinesses();
        $existing = new Business();
        $existing->id = 2;
        $existing->slug = 'cafe';
        $repo->businesses[] = $existing;
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $business = $service->create(7, 'Café');
        self::assertSame('cafe-2', $business->slug);
        $this->expectException(DomainException::class);
        $service->create(7, 'Another');
    }

    public function testBusinessCreationSeedsAndPaymentMethodsAreManaged(): void
    {
        $repo = new ManagementBusinesses(); $payments = new ManagementPaymentMethods();
        $business = (new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage(), $payments))->create(7, 'Cafe');
        self::assertSame('Efectivo', $payments->items[0]->name);
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage(), $payments);
        $added = $service->addPaymentMethod(7, (int) $business->id, 'Transferencia');
        $service->updatePaymentMethod(7, (int) $business->id, (int) $added->id, ['name' => 'Tarjeta', 'position' => 0]);
        $service->deletePaymentMethod(7, (int) $business->id, (int) $payments->items[0]->id);
        $this->expectException(DomainException::class);
        $service->deletePaymentMethod(7, (int) $business->id, (int) $added->id);
    }

    public function testBusinessCreationSeedsFourOrderStatuses(): void
    {
        $repo = new ManagementBusinesses();
        $statuses = new ManagementOrderStatuses();
        $business = (new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage(), null, $statuses))->create(7, 'Cafe');
        self::assertSame(['Creado', 'Elaborando', 'Entregado', 'Cancelado'], array_map(static fn(OrderStatus $s): string => $s->name, $statuses->findByBusinessId((int) $business->id)));
        self::assertSame(1, count(array_filter($statuses->items, static fn(OrderStatus $s): bool => $s->isDefault)));
        self::assertSame(2, count(array_filter($statuses->items, static fn(OrderStatus $s): bool => $s->isTerminal)));
    }

    public function testOrderStatusCrudDefaultColorAndDeleteGuards(): void
    {
        $businesses = new ManagementBusinesses();
        $businesses->businesses[] = $this->business(1);
        $businesses->members[] = $this->member(1, 7);
        $statuses = new ManagementOrderStatuses();
        foreach ([['Creado', true, false], ['Otro', false, false]] as [$name, $default, $terminal]) {
            $status = new OrderStatus(); $status->id = count($statuses->items) + 1; $status->businessId = 1; $status->name = $name; $status->isDefault = $default; $status->isTerminal = $terminal; $statuses->items[] = $status;
        }
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage(), null, $statuses);
        $added = $service->addOrderStatus(7, 1, ['name' => 'En camino', 'color' => '#abcdef', 'is_terminal' => true]);
        $service->updateOrderStatus(7, 1, (int) $added->id, ['name' => 'En ruta', 'color' => '#ABCDEF', 'position' => 0]);
        $service->setDefaultOrderStatus(7, 1, (int) $added->id);
        self::assertTrue($added->isDefault);
        self::assertFalse($statuses->items[0]->isDefault);
        $this->expectExceptionMessage('Invalid order status color.');
        $service->addOrderStatus(7, 1, ['name' => 'Bad', 'color' => 'orange']);
    }

    public function testOrderStatusDeleteRejectsLastDefaultAndInUseAndAllowsUnused(): void
    {
        $businesses = new ManagementBusinesses(); $businesses->businesses[] = $this->business(1); $businesses->members[] = $this->member(1, 7);
        $statuses = new ManagementOrderStatuses(); $default = new OrderStatus(); $default->id = 1; $default->businessId = 1; $default->name = 'Creado'; $default->isDefault = true; $statuses->items[] = $default;
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage(), null, $statuses);
        try { $service->deleteOrderStatus(7, 1, 1); self::fail(); } catch (DomainException $e) { self::assertSame('At least one order status must remain.', $e->getMessage()); }
        $unused = new OrderStatus(); $unused->id = 2; $unused->businessId = 1; $unused->name = 'Unused'; $statuses->items[] = $unused;
        try { $service->deleteOrderStatus(7, 1, 1); self::fail(); } catch (DomainException $e) { self::assertSame('Set a different default status first.', $e->getMessage()); }
        $statuses->referenced = true;
        try { $service->deleteOrderStatus(7, 1, 2); self::fail(); } catch (DomainException $e) { self::assertSame('Order status is assigned to an order.', $e->getMessage()); }
        $statuses->referenced = false; $service->deleteOrderStatus(7, 1, 2); self::assertCount(1, $statuses->items);
    }

    public function testFulfillmentFeesCanBeSetAndCleared(): void
    {
        $repo = new ManagementBusinesses(); $business = $this->business(1); $repo->businesses[] = $business; $repo->members[] = $this->member(1, 7);
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $service->updateProfile(7, 1, ['delivery_enabled' => true, 'delivery_fee' => 30]);
        self::assertSame('30.00', $business->deliveryFee);
        $service->updateProfile(7, 1, ['delivery_fee' => null]);
        self::assertNull($business->deliveryFee);
    }

    public function testPublishingRejectsEmptyPaymentMethods(): void
    {
        $repo = new ManagementBusinesses(); $business = $this->business(1); $business->latitude = 1; $business->longitude = 1; $repo->businesses[] = $business; $repo->members[] = $this->member(1, 7);
        $this->expectException(DomainException::class);
        (new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage(), new ManagementPaymentMethods()))->setPublished(7, 1, true);
    }

    public function testFindByOwnerReturnsBusinessAndHoursFromMember(): void
    {
        $repo = new ManagementBusinesses();
        $business = $this->business(1);
        $repo->businesses[] = $business;
        $repo->members[] = $this->member(1, 7);
        $hours = new BusinessHours();
        $hours->businessId = 1;
        $repo->hours[] = $hours;
        $result = (new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage()))->findByOwner(7);
        self::assertSame($business, $result['business']);
        self::assertSame([$hours], $result['hours']);
    }

    public function testProfileValidationHoursAndGuard(): void
    {
        $repo = new ManagementBusinesses();
        $business = $this->business(1);
        $repo->businesses[] = $business;
        $repo->members[] = $this->member(1, 7);
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $service->updateProfile(7, 1, ['name' => 'Updated', 'whatsapp_number' => '+52 55 1234 5678', 'description' => 'About'], 'logo', 'image/png');
        self::assertSame('Updated', $business->name);
        self::assertSame('+52 55 1234 5678', $business->whatsappNumber);
        self::assertSame('https://objects/businesses/1/logo', $business->logoUrl);
        $service->updateProfile(7, 1, ['category' => 'cafe', 'location' => 'Roma Norte']);
        self::assertSame('cafe', $business->category);
        self::assertSame('Roma Norte', $business->location);
        $this->expectException(DomainException::class);
        $service->updateProfile(7, 1, ['whatsapp_number' => 'not-a-phone']);
    }

    public function testProfileRejectsInvalidCategoryAndAllowsUnsetFields(): void
    {
        $repo = new ManagementBusinesses();
        $business = $this->business(1);
        $repo->businesses[] = $business;
        $repo->members[] = $this->member(1, 7);
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $service->updateProfile(7, 1, []);
        self::assertNull($business->category);
        self::assertNull($business->location);
        $this->expectException(DomainException::class);
        $service->updateProfile(7, 1, ['category' => 'invalid']);
    }

    public function testSocialUrlsCanBeSetClearedAndRejectMalformedValues(): void
    {
        $repo = new ManagementBusinesses();
        $business = $this->business(1);
        $repo->businesses[] = $business;
        $repo->members[] = $this->member(1, 7);
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $service->updateProfile(7, 1, ['facebook_url' => ' https://facebook.com/shop ', 'instagram_url' => 'https://instagram.com/shop', 'website_url' => 'https://shop.example']);
        self::assertSame('https://facebook.com/shop', $business->facebookUrl);
        self::assertSame('https://instagram.com/shop', $business->instagramUrl);
        self::assertSame('https://shop.example', $business->websiteUrl);
        $service->updateProfile(7, 1, ['facebook_url' => '', 'instagram_url' => null, 'website_url' => '   ']);
        self::assertNull($business->facebookUrl);
        self::assertNull($business->instagramUrl);
        self::assertNull($business->websiteUrl);
        foreach (['facebook_url', 'instagram_url', 'website_url'] as $field) {
            try {
                $service->updateProfile(7, 1, [$field => 'invalid URL']);
                self::fail('Expected invalid URL rejection for ' . $field);
            } catch (DomainException $exception) {
                self::assertSame('Invalid ' . $field . '.', $exception->getMessage());
            }
        }
    }

    public function testProfileCoordinatesAreOptionalValidatedAndStored(): void
    {
        $repo = new ManagementBusinesses();
        $business = $this->business(1);
        $repo->businesses[] = $business;
        $repo->members[] = $this->member(1, 7);
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $service->updateProfile(7, 1, ['latitude' => '19.4326', 'longitude' => '-99.1332']);
        self::assertSame(19.4326, $business->latitude);
        self::assertSame(-99.1332, $business->longitude);
        $service->updateProfile(7, 1, ['latitude' => null, 'longitude' => null]);
        self::assertNull($business->latitude);
        self::assertNull($business->longitude);
        foreach ([['latitude' => 91, 'longitude' => 0], ['latitude' => 0, 'longitude' => 181], ['latitude' => 0]] as $input) {
            try {
                $service->updateProfile(7, 1, $input);
                self::fail('Expected invalid coordinates.');
            } catch (DomainException $e) {
                self::assertSame('Invalid coordinates.', $e->getMessage());
            }
        }
    }

    public function testProfileUploadsAndReplacesCoverImage(): void
    {
        $repo = new ManagementBusinesses();
        $business = $this->business(1);
        $repo->businesses[] = $business;
        $repo->members[] = $this->member(1, 7);
        $storage = new FakeStorage();
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), $storage);
        $service->updateProfile(7, 1, [], null, 'application/octet-stream', 'first', 'image/png');
        $service->updateProfile(7, 1, [], null, 'application/octet-stream', 'second', 'image/jpeg');
        self::assertSame('https://objects/businesses/1/cover', $business->coverImageUrl);
        self::assertSame(['businesses/1/cover', 'businesses/1/cover'], $storage->keys);
    }

    public function testClosedHoursAndNonMemberRejection(): void
    {
        $repo = new ManagementBusinesses();
        $repo->businesses[] = $this->business(1);
        $repo->members[] = $this->member(1, 7);
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $hours = $service->updateHours(7, 1, [['day_of_week' => 0, 'opens_at' => '09:00', 'closes_at' => '18:00'], ['day_of_week' => 1, 'is_closed' => true, 'opens_at' => '09:00']]);
        self::assertSame('09:00', $hours[0]->opensAt);
        self::assertTrue($hours[1]->isClosed);
        self::assertNull($hours[1]->opensAt);
        $this->expectException(ForbiddenException::class);
        (new BusinessMemberGuard($repo))->assertOwner(99, 1);
    }

    public function testCategoryDeletionIsBlockedWhenProductsExist(): void
    {
        $businesses = new ManagementBusinesses();
        $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories();
        $category = new Category();
        $category->id = 3;
        $category->businessId = 1;
        $categories->items[] = $category;
        $products = new ManagementProducts();
        $product = new Product();
        $product->categoryId = 3;
        $products->items[] = $product;
        $service = new CategoryService($categories, $products, new BusinessMemberGuard($businesses));
        $this->expectException(DomainException::class);
        $service->delete(7, 1, 3);
    }

    public function testCategoryDeletionIgnoresSoftDeletedProducts(): void
    {
        $businesses = new ManagementBusinesses();
        $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories();
        $category = new Category();
        $category->id = 3;
        $category->businessId = 1;
        $categories->items[] = $category;
        $products = new ManagementProducts();
        $product = new Product();
        $product->categoryId = 3;
        $product->deletedAt = date(DATE_ATOM);
        $products->items[] = $product;
        (new CategoryService($categories, $products, new BusinessMemberGuard($businesses)))->delete(7, 1, 3);
        self::assertNotNull($category->deletedAt);
    }

    public function testProductDeletionIsSoft(): void
    {
        $businesses = new ManagementBusinesses();
        $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories();
        $category = new Category();
        $category->id = 4;
        $category->businessId = 1;
        $categories->items[] = $category;
        $products = new ManagementProducts();
        $product = new Product();
        $product->id = 5;
        $product->businessId = 1;
        $products->items[] = $product;
        (new ProductService($products, $categories, new BusinessMemberGuard($businesses), new FakeStorage()))->delete(7, 1, 5);
        self::assertNotNull($product->deletedAt);
    }

    public function testProductPricesToggleAndImageReplacement(): void
    {
        $businesses = new ManagementBusinesses();
        $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories();
        $category = new Category();
        $category->id = 4;
        $category->businessId = 1;
        $categories->items[] = $category;
        $products = new ManagementProducts();
        $service = new ProductService($products, $categories, new BusinessMemberGuard($businesses), new FakeStorage());
        $priced = $service->create(7, 1, ['name' => 'Burger', 'category_id' => 4, 'price' => '12.50'], 'one', 'image/png');
        $free = $service->create(7, 1, ['name' => 'Consulting', 'category_id' => 4]);
        self::assertSame('12.50', $priced->price);
        self::assertNull($free->price);
        $service->setActive(7, 1, (int) $priced->id, false);
        self::assertFalse($priced->isActive);
        $service->update(7, 1, (int) $priced->id, [], 'two', 'image/png');
        self::assertSame('https://objects/products/1/image', $products->image?->url);
    }

    public function testProductIngredientsCreateWithoutAndReplaceOnUpdate(): void
    {
        $businesses = new ManagementBusinesses();
        $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories();
        $category = new Category();
        $category->id = 4;
        $category->businessId = 1;
        $categories->items[] = $category;
        $ingredients = new ManagementIngredients();
        $products = new ManagementProducts();
        $service = new ProductService($products, $categories, new BusinessMemberGuard($businesses), new FakeStorage(), $ingredients);
        $product = $service->create(7, 1, ['name' => 'Burger', 'category_id' => 4, 'ingredients' => ['Tomato', 'Cheese']]);
        self::assertSame(['Tomato', 'Cheese'], $ingredients->for((int) $product->id));
        $plain = $service->create(7, 1, ['name' => 'Water', 'category_id' => 4]);
        self::assertSame([], $ingredients->for((int) $plain->id));
        $service->update(7, 1, (int) $product->id, ['ingredients' => ['Onion']]);
        self::assertSame(['Onion'], $ingredients->for((int) $product->id));
    }

    public function testProductOptionsReplaceAndRejectPricedOptionOnPricelessProduct(): void
    {
        $businesses = new ManagementBusinesses();
        $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories();
        $category = new Category();
        $category->id = 4;
        $category->businessId = 1;
        $categories->items[] = $category;
        $options = new ManagementOptions();
        $service = new ProductService(new ManagementProducts(), $categories, new BusinessMemberGuard($businesses), new FakeStorage(), null, $options);
        $product = $service->create(7, 1, ['name' => 'Coffee', 'category_id' => 4, 'price' => '50', 'options' => [['name' => 'Sugar', 'selection_type' => 'single', 'required' => true, 'values' => [['name' => 'Brown sugar', 'price_delta' => 0]]]]]);
        self::assertCount(1, $options->for((int) $product->id));
        $this->expectException(DomainException::class);
        $service->create(7, 1, ['name' => 'Service', 'category_id' => 4, 'options' => [['name' => 'Add-on', 'selection_type' => 'multiple', 'values' => [['name' => 'Rush', 'price_delta' => 15]]]]]);
    }

    public function testControllerParsesMultipartIngredientJsonField(): void
    {
        $businesses = new ManagementBusinesses();
        $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories();
        $category = new Category();
        $category->id = 4;
        $category->businessId = 1;
        $categories->items[] = $category;
        $products = new ManagementProducts();
        $ingredients = new ManagementIngredients();
        $service = new ProductService($products, $categories, new BusinessMemberGuard($businesses), new FakeStorage(), $ingredients);
        $route = new CurrentRoute();
        $route->setRouteWithArguments(Route::post('/businesses/{businessId}/products'), ['businessId' => '1']);
        $request = (new ServerRequest(parsedBody: ['name' => 'Burger', 'category_id' => 4, 'ingredients' => '["Tomato","Cheese"]']))->withAttribute('user_id', 7);
        $response = (new \App\Web\Catalog\CatalogController(new CategoryService($categories, $products, new BusinessMemberGuard($businesses)), $service, new ResponseFactory(), new StreamFactory(), $route))->createProduct($request);
        self::assertSame(['Tomato', 'Cheese'], $ingredients->for((int) $products->items[0]->id));
        self::assertSame(201, $response->getStatusCode());
    }

    public function testPublishAndUnpublishAffectsPublicCatalog(): void
    {
        $businesses = new ManagementBusinesses();
        $business = $this->business(1);
        $business->slug = 'shop';
        $business->latitude = 19.4326;
        $business->longitude = -99.1332;
        $businesses->businesses[] = $business;
        $businesses->members[] = $this->member(1, 7);
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage());
        $service->setPublished(7, 1, true);
        self::assertNotNull((new CatalogService($businesses, new PublicCategories(), new PublicProducts()))->publicCatalog('shop'));
        $service->setPublished(7, 1, false);
        self::assertNull((new CatalogService($businesses, new PublicCategories(), new PublicProducts()))->publicCatalog('shop'));
    }

    public function testPublishingRequiresBothCoordinates(): void
    {
        $businesses = new ManagementBusinesses();
        $business = $this->business(1);
        $businesses->businesses[] = $business;
        $businesses->members[] = $this->member(1, 7);
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Set a location on the map before publishing.');
        $service->setPublished(7, 1, true);
        self::assertFalse($business->isPublished);
    }

    public function testPublishingSucceedsWithCoordinates(): void
    {
        $businesses = new ManagementBusinesses();
        $business = $this->business(1);
        $business->latitude = 19.4326;
        $business->longitude = -99.1332;
        $businesses->businesses[] = $business;
        $businesses->members[] = $this->member(1, 7);
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage());

        $service->setPublished(7, 1, true);

        self::assertTrue($business->isPublished);
    }

    public function testUnpublishingDoesNotRequireCoordinates(): void
    {
        $businesses = new ManagementBusinesses();
        $business = $this->business(1);
        $businesses->businesses[] = $business;
        $businesses->members[] = $this->member(1, 7);
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage());

        $service->setPublished(7, 1, false);
        self::assertFalse($business->isPublished);
        $business->isPublished = true;
        $service->setPublished(7, 1, false);
        self::assertFalse($business->isPublished);
    }

    public function testAlreadyPublishedCoordinateLessBusinessIsUnaffected(): void
    {
        $businesses = new ManagementBusinesses();
        $business = $this->business(1);
        $business->isPublished = true;
        $businesses->businesses[] = $business;
        $businesses->members[] = $this->member(1, 7);
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage());

        $service->setPublished(7, 1, true);

        self::assertTrue($business->isPublished);
    }

    public function testFulfillmentMethodsKeepOneEnabled(): void
    {
        $businesses = new ManagementBusinesses();
        $business = $this->business(1);
        $businesses->businesses[] = $business;
        $businesses->members[] = $this->member(1, 7);
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage());
        $service->updateProfile(7, 1, ['pickup_enabled' => false, 'delivery_enabled' => true]);
        self::assertFalse($business->pickupEnabled);
        self::assertTrue($business->deliveryEnabled);
        $this->expectException(DomainException::class);
        $service->updateProfile(7, 1, ['delivery_enabled' => false]);
        self::assertTrue($business->deliveryEnabled);
    }

    public function testPublishingRejectsNoFulfillmentMethodsAfterCoordinateCheck(): void
    {
        $businesses = new ManagementBusinesses();
        $business = $this->business(1);
        $business->latitude = 19.4326;
        $business->longitude = -99.1332;
        $business->pickupEnabled = $business->deliveryEnabled = $business->dineInEnabled = false;
        $businesses->businesses[] = $business;
        $businesses->members[] = $this->member(1, 7);
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage());
        $this->expectExceptionMessage('At least one fulfillment method must be enabled.');
        $service->setPublished(7, 1, true);
    }

    private function business(int $id): Business
    {
        $b = new Business();
        $b->id = $id;
        $b->slug = 'business';
        $b->name = 'Business';
        return $b;
    }
    private function member(int $businessId, int $userId): BusinessMember
    {
        $m = new BusinessMember();
        $m->businessId = $businessId;
        $m->userId = $userId;
        return $m;
    }
}

final class FakeStorage implements ObjectStorageInterface
{
    public array $keys = [];
    public function put(string $key, string $contents, string $contentType): string
    {
        $this->keys[] = $key;
        return 'https://objects/' . $key;
    }
}
final class ManagementBusinesses implements BusinessManagementRepositoryInterface
{
    public array $businesses = [];
    public array $members = [];
    public array $hours = [];
    public function create(Business $entity): Business
    {
        $entity->id ??= count($this->businesses) + 1;
        $this->businesses[] = $entity;
        return $entity;
    }
    public function findById(int $id): ?Business
    {
        foreach ($this->businesses as $b) {
            if ($b->id === $id) {
                return $b;
            }
        } return null;
    }
    public function findPublishedBySlug(string $slug): ?Business
    {
        foreach ($this->businesses as $b) {
            if ($b->slug === $slug && $b->isPublished) {
                return $b;
            }
        } return null;
    }
    public function findPublishedDirectory(?string $category, ?string $location, ?float $latitude = null, ?float $longitude = null, ?string $name = null): array
    {
        return [];
    }
    public function findHours(int $businessId): array
    {
        return array_values(array_filter($this->hours, fn(BusinessHours $h) => $h->businessId === $businessId));
    }
    public function findByOwnerUserId(int $userId): ?Business
    {
        foreach ($this->businesses as $b) {
            if ($b->ownerUserId === $userId) {
                return $b;
            }
        } return null;
    }
    public function findByMemberUserId(int $userId): ?Business
    {
        foreach ($this->members as $m) {
            if ($m->userId === $userId) {
                return $this->findById($m->businessId);
            }
        } return null;
    }
    public function findBySlug(string $slug): ?Business
    {
        foreach ($this->businesses as $b) {
            if ($b->slug === $slug) {
                return $b;
            }
        } return null;
    }
    public function update(Business $business): Business
    {
        return $business;
    }
    public function createMember(BusinessMember $member): BusinessMember
    {
        $this->members[] = $member;
        return $member;
    }
    public function findMember(int $businessId, int $userId): ?BusinessMember
    {
        foreach ($this->members as $m) {
            if ($m->businessId === $businessId && $m->userId === $userId) {
                return $m;
            }
        } return null;
    }
    public function saveHours(BusinessHours $hours): BusinessHours
    {
        $this->hours[] = $hours;
        return $hours;
    }
}
final class ManagementPaymentMethods implements PaymentMethodRepositoryInterface
{
    public array $items = [];
    public function findByBusinessId(int $businessId): array { return array_values(array_filter($this->items, static fn(PaymentMethod $m): bool => $m->businessId === $businessId)); }
    public function create(PaymentMethod $method): PaymentMethod { $method->id ??= count($this->items) + 1; $this->items[] = $method; return $method; }
    public function update(PaymentMethod $method): PaymentMethod { return $method; }
    public function delete(PaymentMethod $method): void { $this->items = array_values(array_filter($this->items, static fn(PaymentMethod $m): bool => $m !== $method)); }
}
final class ManagementOrderStatuses implements OrderStatusRepositoryInterface
{
    public array $items = [];
    public bool $referenced = false;
    public function findByBusinessId(int $businessId): array { return array_values(array_filter($this->items, static fn(OrderStatus $s): bool => $s->businessId === $businessId)); }
    public function findById(int $id): ?OrderStatus { foreach ($this->items as $status) if ((int) $status->id === $id) return $status; return null; }
    public function create(OrderStatus $status): OrderStatus { $status->id ??= count($this->items) + 1; $this->items[] = $status; return $status; }
    public function update(OrderStatus $status): OrderStatus { return $status; }
    public function delete(OrderStatus $status): void { $this->items = array_values(array_filter($this->items, static fn(OrderStatus $s): bool => $s !== $status)); }
    public function existsOrderWithStatus(int $statusId): bool { return $this->referenced; }
}
final class ManagementCategories implements CategoryManagementRepositoryInterface
{
    public array $items = [];
    public function create(Category $entity): Category
    {
        $entity->id ??= count($this->items) + 1;
        $this->items[] = $entity;
        return $entity;
    }
    public function findById(int $id): ?Category
    {
        foreach ($this->items as $c) {
            if ($c->id === $id) {
                return $c;
            }
        } return null;
    }
    public function findByBusinessId(int $id): array
    {
        return array_values(array_filter($this->items, fn(Category $c) => $c->businessId === $id));
    }
    public function update(Category $category): Category
    {
        return $category;
    }
    public function delete(Category $category): void {}
}
final class ManagementProducts implements ProductManagementRepositoryInterface
{
    public array $items = [];
    public ?ProductImage $image = null;
    public function create(Product $entity): Product
    {
        $entity->id ??= count($this->items) + 1;
        $this->items[] = $entity;
        return $entity;
    }
    public function findById(int $id): ?Product
    {
        foreach ($this->items as $p) {
            if ($p->id === $id) {
                return $p;
            }
        } return null;
    }
    public function findActiveByBusinessId(int $id): array
    {
        return array_values(array_filter($this->items, fn(Product $p) => $p->businessId === $id && $p->isActive));
    }
    public function findActiveByIdsForBusiness(array $ids, int $id): array
    {
        return [];
    }
    public function update(Product $product): Product
    {
        return $product;
    }
    public function delete(Product $product): void {}
    public function findByBusinessId(int $id): array
    {
        return $this->items;
    }
    public function findByCategoryId(int $id): array
    {
        return array_values(array_filter($this->items, fn(Product $p) => $p->categoryId === $id && $p->deletedAt === null));
    }
    public function findImage(int $id): ?ProductImage
    {
        return $this->image;
    }
    public function saveImage(ProductImage $image): ProductImage
    {
        $this->image = $image;
        return $image;
    }
    public function deleteImage(ProductImage $image): void {}
}
final class ManagementIngredients implements \App\Domain\Repository\ProductIngredientRepositoryInterface
{
    public array $items = [];
    public function replaceForProduct(int $productId, array $names): void
    {
        $this->items[$productId] = array_values($names);
    }
    public function findByProductIds(array $productIds): array
    {
        return array_intersect_key($this->items, array_flip($productIds));
    }
    public function for(int $productId): array
    {
        return $this->items[$productId] ?? [];
    }
}
final class ManagementOptions implements ProductOptionRepositoryInterface
{
    public array $items = [];
    public function replaceForProduct(int $productId, array $options): void
    {
        $this->items[$productId] = array_map(static function (array $input, int $position): ProductOption {
            $option = new ProductOption();
            $option->productId = 1;
            $option->name = $input['name'];
            $option->selectionType = $input['selection_type'];
            $option->required = (bool) ($input['required'] ?? false);
            $option->position = $position;
            return $option;
        }, $options, array_keys($options));
    }
    public function findByProductIds(array $productIds): array
    {
        return [];
    }
    public function for(int $productId): array
    {
        return $this->items[$productId] ?? [];
    }
}
final class PublicCategories implements \App\Domain\Repository\CategoryRepositoryInterface
{
    public function create(Category $entity): Category
    {
        return $entity;
    } public function findById(int $id): ?Category
    {
        return null;
    } public function findByBusinessId(int $id): array
    {
        return [];
    }
}
final class PublicProducts implements \App\Domain\Repository\ProductRepositoryInterface
{
    public function create(Product $entity): Product
    {
        return $entity;
    } public function findById(int $id): ?Product
    {
        return null;
    } public function findActiveByBusinessId(int $id): array
    {
        return [];
    } public function findActiveByIdsForBusiness(array $ids, int $id): array
    {
        return [];
    }
}
final class PublicIngredients implements \App\Domain\Repository\ProductIngredientRepositoryInterface
{
    public int $loads = 0;
    public function __construct(private array $items = []) {}
    public function replaceForProduct(int $productId, array $names): void {}
    public function findByProductIds(array $productIds): array
    {
        $this->loads++;
        return $this->items;
    }
}
