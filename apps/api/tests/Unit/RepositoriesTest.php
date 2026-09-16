<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Entity\{Business, BusinessMember, Category, Order, Product, User};
use App\Infrastructure\Cycle\Repository\{CycleBusinessRepository, CycleCategoryRepository, CycleOrderRepository, CycleProductIngredientRepository, CycleProductRepository, CycleUserRepository};
use App\Domain\Service\{BusinessMemberGuard, CategoryService, CatalogService, OrderService};
use DomainException;
use PHPUnit\Framework\TestCase;

final class RepositoriesTest extends TestCase
{
    public function testUserCreateAndFetchById(): void
    {
        $repository = new CycleUserRepository();
        $user = new User();
        $user->id = $this->nextId('users');
        $user->email = $this->uniqueEmail();
        $user->passwordHash = 'hash';
        $user->createdAt = date(DATE_ATOM);
        $repository->create($user);
        $found = $repository->findById((int) $user->id);
        self::assertNotNull($found);
        self::assertSame($user->email, $found->email);
    }

    public function testBusinessCreateAndFetchById(): void
    {
        $owner = $this->createUser();
        $repository = new CycleBusinessRepository();
        $business = new Business();
        $business->id = $this->nextId('businesses');
        $business->ownerUserId = (int) $owner->id;
        $business->name = 'Test business';
        $business->slug = 'test-' . uniqid();
        $business->createdAt = date(DATE_ATOM);
        $repository->create($business);
        $found = $repository->findById((int) $business->id);
        self::assertNotNull($found);
        self::assertSame($business->slug, $found->slug);
    }

    public function testCategoryCreateAndFetchById(): void
    {
        $business = $this->createBusiness();
        $repository = new CycleCategoryRepository();
        $category = new Category();
        $category->businessId = (int) $business->id;
        $category->name = 'Drinks';
        $repository->create($category);
        $found = $repository->findById((int) $category->id);
        self::assertNotNull($found);
        self::assertSame('Drinks', $found->name);
    }

    public function testProductCreateAndFetchById(): void
    {
        $business = $this->createBusiness();
        $category = new Category();
        $category->businessId = (int) $business->id;
        $category->name = 'Food';
        (new CycleCategoryRepository())->create($category);
        $repository = new CycleProductRepository();
        $product = new Product();
        $product->businessId = (int) $business->id;
        $product->categoryId = (int) $category->id;
        $product->name = 'Priceless service';
        $product->createdAt = date(DATE_ATOM);
        $repository->create($product);
        $found = $repository->findById((int) $product->id);
        self::assertNotNull($found);
        self::assertSame($product->name, $found->name);
        self::assertNull($found->price);
    }

    public function testProductIngredientsReplaceOnSave(): void
    {
        $business = $this->createBusiness();
        $category = new Category(); $category->businessId = (int) $business->id; $category->name = 'Food';
        (new CycleCategoryRepository())->create($category);
        $product = new Product(); $product->businessId = (int) $business->id; $product->categoryId = (int) $category->id; $product->name = 'Burger'; $product->createdAt = date(DATE_ATOM);
        (new CycleProductRepository())->create($product);
        $ingredients = new CycleProductIngredientRepository();
        $ingredients->replaceForProduct((int) $product->id, ['Tomato', 'Cheese']);
        $ingredients->replaceForProduct((int) $product->id, ['Onion']);
        self::assertSame([(int) $product->id => ['Onion']], $ingredients->findByProductIds([(int) $product->id]));
    }

    public function testOrderCreateAndFetchById(): void
    {
        $business = $this->createBusiness();
        $repository = new CycleOrderRepository();
        $order = new Order();
        $order->businessId = (int) $business->id;
        $order->total = '12.50';
        $order->customerNote = 'No onions';
        $order->createdAt = date(DATE_ATOM);
        $repository->create($order);
        $found = $repository->findById((int) $order->id);
        self::assertNotNull($found);
        self::assertSame('12.50', $found->total);
        self::assertSame('No onions', $found->customerNote);
    }

    public function testSoftDeletedCatalogRowsRemainButAreHidden(): void
    {
        $business = $this->createBusiness(true);
        $category = new Category();
        $category->businessId = (int) $business->id;
        $category->name = 'Food';
        $categoryRepository = new CycleCategoryRepository();
        $categoryRepository->create($category);
        $product = new Product();
        $product->businessId = (int) $business->id;
        $product->categoryId = (int) $category->id;
        $product->name = 'Burger';
        $product->createdAt = date(DATE_ATOM);
        $productRepository = new CycleProductRepository();
        $productRepository->create($product);

        $product->deletedAt = date(DATE_ATOM);
        $productRepository->update($product);
        self::assertNotNull($this->deletedAt('products', (int) $product->id));
        self::assertNull($productRepository->findById((int) $product->id));
        self::assertSame([], $productRepository->findActiveByBusinessId((int) $business->id));
        self::assertSame([], $productRepository->findByBusinessId((int) $business->id));
        self::assertSame([], (new CatalogService(new CycleBusinessRepository(), $categoryRepository, $productRepository))
            ->publicCatalog($business->slug)['categories'][0]['products']);

        $category->deletedAt = date(DATE_ATOM);
        $categoryRepository->update($category);
        self::assertNotNull($this->deletedAt('categories', (int) $category->id));
        self::assertNull($categoryRepository->findById((int) $category->id));
        self::assertSame([], $categoryRepository->findByBusinessId((int) $business->id));
        self::assertSame([], (new CatalogService(new CycleBusinessRepository(), $categoryRepository, $productRepository))
            ->publicCatalog($business->slug)['categories']);
    }

    public function testActiveProductsBlockCategorySoftDelete(): void
    {
        $business = $this->createBusiness();
        $member = new BusinessMember();
        $member->businessId = (int) $business->id;
        $member->userId = (int) $business->ownerUserId;
        $member->createdAt = date(DATE_ATOM);
        (new CycleBusinessRepository())->createMember($member);
        $category = new Category();
        $category->businessId = (int) $business->id;
        $category->name = 'Food';
        (new CycleCategoryRepository())->create($category);
        $product = new Product();
        $product->businessId = (int) $business->id;
        $product->categoryId = (int) $category->id;
        $product->name = 'Burger';
        $product->createdAt = date(DATE_ATOM);
        (new CycleProductRepository())->create($product);

        $this->expectException(DomainException::class);
        (new CategoryService(new CycleCategoryRepository(), new CycleProductRepository(), new BusinessMemberGuard(new CycleBusinessRepository())))
            ->delete((int) $business->ownerUserId, (int) $business->id, (int) $category->id);
    }

    public function testSoftDeletedProductCannotCreateRealOrder(): void
    {
        $business = $this->createBusiness(true, '+525500000000');
        $businessRepository = new CycleBusinessRepository();
        $category = new Category();
        $category->businessId = (int) $business->id;
        $category->name = 'Food';
        (new CycleCategoryRepository())->create($category);
        $product = new Product();
        $product->businessId = (int) $business->id;
        $product->categoryId = (int) $category->id;
        $product->name = 'Burger';
        $product->createdAt = date(DATE_ATOM);
        $products = new CycleProductRepository();
        $products->create($product);
        $product->deletedAt = date(DATE_ATOM);
        $products->update($product);

        $this->expectException(DomainException::class);
        (new OrderService($businessRepository, $products, new CycleOrderRepository()))
            ->create($business->slug, ['items' => [['product_id' => $product->id, 'quantity' => 1]]]);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->id = $this->nextId('users');
        $user->email = $this->uniqueEmail();
        $user->passwordHash = 'hash';
        $user->createdAt = date(DATE_ATOM);
        return (new CycleUserRepository())->create($user);
    }

    private function createBusiness(bool $published = false, ?string $whatsappNumber = null): Business
    {
        $business = new Business();
        $business->id = $this->nextId('businesses');
        $business->ownerUserId = (int) $this->createUser()->id;
        $business->name = 'Test business';
        $business->slug = 'test-' . uniqid();
        $business->isPublished = $published;
        $business->whatsappNumber = $whatsappNumber;
        $business->createdAt = date(DATE_ATOM);
        return (new CycleBusinessRepository())->create($business);
    }

    private function uniqueEmail(): string
    {
        return 'test-' . uniqid('', true) . '@example.test';
    }

    private function deletedAt(string $table, int $id): ?string
    {
        $pdo = new \PDO(
            getenv('DATABASE_URL') ?: 'pgsql:host=postgres;port=5432;dbname=vendaly',
            getenv('POSTGRES_USER') ?: 'vendaly',
            getenv('POSTGRES_PASSWORD') ?: 'vendaly',
        );
        $statement = $pdo->prepare('SELECT deleted_at FROM ' . $table . ' WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetchColumn() ?: null;
    }

    private function nextId(string $table): int
    {
        $pdo = new \PDO(
            getenv('DATABASE_URL') ?: 'pgsql:host=postgres;port=5432;dbname=vendaly',
            getenv('POSTGRES_USER') ?: 'vendaly',
            getenv('POSTGRES_PASSWORD') ?: 'vendaly',
        );
        $id = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM ' . $table)->fetchColumn();
        $pdo->exec("SELECT setval('{$table}_id_seq', {$id}, true)");
        return $id;
    }
}
