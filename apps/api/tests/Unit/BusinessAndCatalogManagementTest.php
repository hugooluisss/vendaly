<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Entity\{Business, BusinessHours, BusinessMember, Category, Product, ProductImage};
use App\Domain\Exception\ForbiddenException;
use App\Domain\Repository\{BusinessManagementRepositoryInterface, CategoryManagementRepositoryInterface, ObjectStorageInterface, ProductManagementRepositoryInterface};
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
        $existing = new Business(); $existing->id = 2; $existing->slug = 'cafe'; $repo->businesses[] = $existing;
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $business = $service->create(7, 'Café');
        self::assertSame('cafe-2', $business->slug);
        $this->expectException(DomainException::class);
        $service->create(7, 'Another');
    }

    public function testFindByOwnerReturnsBusinessAndHoursFromMember(): void
    {
        $repo = new ManagementBusinesses(); $business = $this->business(1); $repo->businesses[] = $business; $repo->members[] = $this->member(1, 7);
        $hours = new BusinessHours(); $hours->businessId = 1; $repo->hours[] = $hours;
        $result = (new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage()))->findByOwner(7);
        self::assertSame($business, $result['business']); self::assertSame([$hours], $result['hours']);
    }

    public function testProfileValidationHoursAndGuard(): void
    {
        $repo = new ManagementBusinesses();
        $business = $this->business(1); $repo->businesses[] = $business;
        $repo->members[] = $this->member(1, 7);
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $service->updateProfile(7, 1, ['name' => 'Updated', 'whatsapp_number' => '+52 55 1234 5678', 'description' => 'About'], 'logo', 'image/png');
        self::assertSame('Updated', $business->name);
        self::assertSame('https://objects/businesses/1/logo', $business->logoUrl);
        $this->expectException(DomainException::class);
        $service->updateProfile(7, 1, ['whatsapp_number' => 'not-a-phone']);
    }

    public function testClosedHoursAndNonMemberRejection(): void
    {
        $repo = new ManagementBusinesses(); $repo->businesses[] = $this->business(1); $repo->members[] = $this->member(1, 7);
        $service = new BusinessService($repo, new BusinessMemberGuard($repo), new FakeStorage());
        $hours = $service->updateHours(7, 1, [['day_of_week' => 0, 'opens_at' => '09:00', 'closes_at' => '18:00'], ['day_of_week' => 1, 'is_closed' => true, 'opens_at' => '09:00']]);
        self::assertSame('09:00', $hours[0]->opensAt); self::assertTrue($hours[1]->isClosed); self::assertNull($hours[1]->opensAt);
        $this->expectException(ForbiddenException::class);
        (new BusinessMemberGuard($repo))->assertOwner(99, 1);
    }

    public function testCategoryDeletionIsBlockedWhenProductsExist(): void
    {
        $businesses = new ManagementBusinesses(); $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories(); $category = new Category(); $category->id = 3; $category->businessId = 1; $categories->items[] = $category;
        $products = new ManagementProducts(); $product = new Product(); $product->categoryId = 3; $products->items[] = $product;
        $service = new CategoryService($categories, $products, new BusinessMemberGuard($businesses));
        $this->expectException(DomainException::class);
        $service->delete(7, 1, 3);
    }

    public function testCategoryDeletionIgnoresSoftDeletedProducts(): void
    {
        $businesses = new ManagementBusinesses(); $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories(); $category = new Category(); $category->id = 3; $category->businessId = 1; $categories->items[] = $category;
        $products = new ManagementProducts(); $product = new Product(); $product->categoryId = 3; $product->deletedAt = date(DATE_ATOM); $products->items[] = $product;
        (new CategoryService($categories, $products, new BusinessMemberGuard($businesses)))->delete(7, 1, 3);
        self::assertNotNull($category->deletedAt);
    }

    public function testProductDeletionIsSoft(): void
    {
        $businesses = new ManagementBusinesses(); $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories(); $category = new Category(); $category->id = 4; $category->businessId = 1; $categories->items[] = $category;
        $products = new ManagementProducts(); $product = new Product(); $product->id = 5; $product->businessId = 1; $products->items[] = $product;
        (new ProductService($products, $categories, new BusinessMemberGuard($businesses), new FakeStorage()))->delete(7, 1, 5);
        self::assertNotNull($product->deletedAt);
    }

    public function testProductPricesToggleAndImageReplacement(): void
    {
        $businesses = new ManagementBusinesses(); $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories(); $category = new Category(); $category->id = 4; $category->businessId = 1; $categories->items[] = $category;
        $products = new ManagementProducts(); $service = new ProductService($products, $categories, new BusinessMemberGuard($businesses), new FakeStorage());
        $priced = $service->create(7, 1, ['name' => 'Burger', 'category_id' => 4, 'price' => '12.50'], 'one', 'image/png');
        $free = $service->create(7, 1, ['name' => 'Consulting', 'category_id' => 4]);
        self::assertSame('12.50', $priced->price); self::assertNull($free->price);
        $service->setActive(7, 1, (int) $priced->id, false);
        self::assertFalse($priced->isActive);
        $service->update(7, 1, (int) $priced->id, [], 'two', 'image/png');
        self::assertSame('https://objects/products/1/image', $products->image?->url);
    }

    public function testProductIngredientsCreateWithoutAndReplaceOnUpdate(): void
    {
        $businesses = new ManagementBusinesses(); $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories(); $category = new Category(); $category->id = 4; $category->businessId = 1; $categories->items[] = $category;
        $ingredients = new ManagementIngredients(); $products = new ManagementProducts();
        $service = new ProductService($products, $categories, new BusinessMemberGuard($businesses), new FakeStorage(), $ingredients);
        $product = $service->create(7, 1, ['name' => 'Burger', 'category_id' => 4, 'ingredients' => ['Tomato', 'Cheese']]);
        self::assertSame(['Tomato', 'Cheese'], $ingredients->for((int) $product->id));
        $plain = $service->create(7, 1, ['name' => 'Water', 'category_id' => 4]);
        self::assertSame([], $ingredients->for((int) $plain->id));
        $service->update(7, 1, (int) $product->id, ['ingredients' => ['Onion']]);
        self::assertSame(['Onion'], $ingredients->for((int) $product->id));
    }

    public function testControllerParsesMultipartIngredientJsonField(): void
    {
        $businesses = new ManagementBusinesses(); $businesses->members[] = $this->member(1, 7);
        $categories = new ManagementCategories(); $category = new Category(); $category->id = 4; $category->businessId = 1; $categories->items[] = $category;
        $products = new ManagementProducts(); $ingredients = new ManagementIngredients();
        $service = new ProductService($products, $categories, new BusinessMemberGuard($businesses), new FakeStorage(), $ingredients);
        $route = new CurrentRoute(); $route->setRouteWithArguments(Route::post('/businesses/{businessId}/products'), ['businessId' => '1']);
        $request = (new ServerRequest(parsedBody: ['name' => 'Burger', 'category_id' => 4, 'ingredients' => '["Tomato","Cheese"]']))->withAttribute('user_id', 7);
        $response = (new \App\Web\Catalog\CatalogController(new CategoryService($categories, $products, new BusinessMemberGuard($businesses)), $service, new ResponseFactory(), new StreamFactory(), $route))->createProduct($request);
        self::assertSame(['Tomato', 'Cheese'], $ingredients->for((int) $products->items[0]->id));
        self::assertSame(201, $response->getStatusCode());
    }

    public function testPublishAndUnpublishAffectsPublicCatalog(): void
    {
        $businesses = new ManagementBusinesses(); $business = $this->business(1); $business->slug = 'shop'; $businesses->businesses[] = $business; $businesses->members[] = $this->member(1, 7);
        $service = new BusinessService($businesses, new BusinessMemberGuard($businesses), new FakeStorage());
        $service->setPublished(7, 1, true);
        self::assertNotNull((new CatalogService($businesses, new PublicCategories(), new PublicProducts()))->publicCatalog('shop'));
        $service->setPublished(7, 1, false);
        self::assertNull((new CatalogService($businesses, new PublicCategories(), new PublicProducts()))->publicCatalog('shop'));
    }

    private function business(int $id): Business { $b = new Business(); $b->id = $id; $b->slug = 'business'; $b->name = 'Business'; return $b; }
    private function member(int $businessId, int $userId): BusinessMember { $m = new BusinessMember(); $m->businessId = $businessId; $m->userId = $userId; return $m; }
}

final class FakeStorage implements ObjectStorageInterface
{
    public function put(string $key, string $contents, string $contentType): string { return 'https://objects/' . $key; }
}
final class ManagementBusinesses implements BusinessManagementRepositoryInterface
{
    public array $businesses = []; public array $members = []; public array $hours = [];
    public function create(Business $entity): Business { $entity->id ??= count($this->businesses) + 1; $this->businesses[] = $entity; return $entity; }
    public function findById(int $id): ?Business { foreach ($this->businesses as $b) if ($b->id === $id) return $b; return null; }
    public function findPublishedBySlug(string $slug): ?Business { foreach ($this->businesses as $b) if ($b->slug === $slug && $b->isPublished) return $b; return null; }
    public function findHours(int $businessId): array { return array_values(array_filter($this->hours, fn(BusinessHours $h) => $h->businessId === $businessId)); }
    public function findByOwnerUserId(int $userId): ?Business { foreach ($this->businesses as $b) if ($b->ownerUserId === $userId) return $b; return null; }
    public function findByMemberUserId(int $userId): ?Business { foreach ($this->members as $m) if ($m->userId === $userId) return $this->findById($m->businessId); return null; }
    public function findBySlug(string $slug): ?Business { foreach ($this->businesses as $b) if ($b->slug === $slug) return $b; return null; }
    public function update(Business $business): Business { return $business; }
    public function createMember(BusinessMember $member): BusinessMember { $this->members[] = $member; return $member; }
    public function findMember(int $businessId, int $userId): ?BusinessMember { foreach ($this->members as $m) if ($m->businessId === $businessId && $m->userId === $userId) return $m; return null; }
    public function saveHours(BusinessHours $hours): BusinessHours { $this->hours[] = $hours; return $hours; }
}
final class ManagementCategories implements CategoryManagementRepositoryInterface
{
    public array $items = [];
    public function create(Category $entity): Category { $entity->id ??= count($this->items) + 1; $this->items[] = $entity; return $entity; }
    public function findById(int $id): ?Category { foreach ($this->items as $c) if ($c->id === $id) return $c; return null; }
    public function findByBusinessId(int $id): array { return array_values(array_filter($this->items, fn(Category $c) => $c->businessId === $id)); }
    public function update(Category $category): Category { return $category; }
    public function delete(Category $category): void { }
}
final class ManagementProducts implements ProductManagementRepositoryInterface
{
    public array $items = []; public ?ProductImage $image = null;
    public function create(Product $entity): Product { $entity->id ??= count($this->items) + 1; $this->items[] = $entity; return $entity; }
    public function findById(int $id): ?Product { foreach ($this->items as $p) if ($p->id === $id) return $p; return null; }
    public function findActiveByBusinessId(int $id): array { return array_values(array_filter($this->items, fn(Product $p) => $p->businessId === $id && $p->isActive)); }
    public function findActiveByIdsForBusiness(array $ids, int $id): array { return []; }
    public function update(Product $product): Product { return $product; }
    public function delete(Product $product): void { }
    public function findByBusinessId(int $id): array { return $this->items; }
    public function findByCategoryId(int $id): array { return array_values(array_filter($this->items, fn(Product $p) => $p->categoryId === $id && $p->deletedAt === null)); }
    public function findImage(int $id): ?ProductImage { return $this->image; }
    public function saveImage(ProductImage $image): ProductImage { $this->image = $image; return $image; }
    public function deleteImage(ProductImage $image): void { }
}
final class ManagementIngredients implements \App\Domain\Repository\ProductIngredientRepositoryInterface
{
    public array $items = [];
    public function replaceForProduct(int $productId, array $names): void { $this->items[$productId] = array_values($names); }
    public function findByProductIds(array $productIds): array { return array_intersect_key($this->items, array_flip($productIds)); }
    public function for(int $productId): array { return $this->items[$productId] ?? []; }
}
final class PublicCategories implements \App\Domain\Repository\CategoryRepositoryInterface
{
    public function create(Category $entity): Category { return $entity; } public function findById(int $id): ?Category { return null; } public function findByBusinessId(int $id): array { return []; }
}
final class PublicProducts implements \App\Domain\Repository\ProductRepositoryInterface
{
    public function create(Product $entity): Product { return $entity; } public function findById(int $id): ?Product { return null; } public function findActiveByBusinessId(int $id): array { return []; } public function findActiveByIdsForBusiness(array $ids, int $id): array { return []; }
}
final class PublicIngredients implements \App\Domain\Repository\ProductIngredientRepositoryInterface
{
    public int $loads = 0;
    public function __construct(private array $items = []) {}
    public function replaceForProduct(int $productId, array $names): void { }
    public function findByProductIds(array $productIds): array { $this->loads++; return $this->items; }
}
