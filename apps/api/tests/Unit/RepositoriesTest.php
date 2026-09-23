<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Entity\{Business, BusinessMember, Category, Order, OrderItem, OrderItemOption, PaymentMethod, Product, User};
use App\Infrastructure\Cycle\Repository\{CycleBusinessRepository, CycleCatalogScanRepository, CycleCategoryRepository, CycleCustomerRepository, CycleOrderRepository, CyclePaymentMethodRepository, CycleProductIngredientRepository, CycleProductOptionRepository, CycleProductRepository, CycleUserRepository};
use App\Domain\Service\{BusinessMemberGuard, CategoryService, CatalogService, OrderService};
use DomainException;
use PHPUnit\Framework\TestCase;

final class RepositoriesTest extends TestCase
{
    public function testCatalogScansCanBeRecordedAndCountedByDay(): void
    {
        $business = $this->createBusiness(true);
        $repository = new CycleCatalogScanRepository();
        $repository->record((int) $business->id);
        $repository->record((int) $business->id);
        self::assertSame(2, $repository->countTotal((int) $business->id));
        $days = $repository->countByDay((int) $business->id, new \DateTimeImmutable('-1 day'), new \DateTimeImmutable('+1 day'));
        self::assertCount(1, $days);
        self::assertSame(2, $days[0]['count']);
        self::assertMatchesRegularExpression('/^\\d{4}-\\d{2}-\\d{2}$/', $days[0]['date']);
    }

    public function testCustomerResolutionIsScopedAndAtomic(): void
    {
        $firstBusiness = $this->createBusiness();
        $secondBusiness = $this->createBusiness();
        $repository = new CycleCustomerRepository();
        $phone = '+5255' . random_int(10000000, 99999999);
        $first = $repository->findOrCreateByPhone((int) $firstBusiness->id, $phone);
        self::assertSame($first->id, $repository->findOrCreateByPhone((int) $firstBusiness->id, $phone)->id);
        self::assertNotSame($first->id, $repository->findOrCreateByPhone((int) $secondBusiness->id, $phone)->id);
        self::assertCount(1, $repository->findByIdsForBusiness((int) $firstBusiness->id, [(int) $first->id]));
        self::assertSame([], $repository->findByIdsForBusiness((int) $secondBusiness->id, [(int) $first->id]));

        $concurrentPhone = '+5255' . random_int(10000000, 99999999);
        $script = 'require "vendor/autoload.php"; echo (new \\App\\Infrastructure\\Cycle\\Repository\\CycleCustomerRepository())->findOrCreateByPhone((int) $argv[1], $argv[2])->id;';
        $processes = [];
        foreach ([1, 2] as $_) {
            $processes[] = proc_open(['php', '-r', $script, (string) $firstBusiness->id, $concurrentPhone], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $outputs[] = $pipes;
        }
        $ids = [];
        foreach ($processes as $index => $process) {
            $ids[] = (int) stream_get_contents($outputs[$index][1]);
            $error = stream_get_contents($outputs[$index][2]);
            fclose($outputs[$index][1]);
            fclose($outputs[$index][2]);
            self::assertSame(0, proc_close($process), $error);
        }
        self::assertSame($ids[0], $ids[1]);
        $pdo = new \PDO(getenv('DATABASE_URL'), getenv('POSTGRES_USER'), getenv('POSTGRES_PASSWORD'));
        $query = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE business_id = ? AND phone = ?');
        $query->execute([$firstBusiness->id, $concurrentPhone]);
        self::assertSame(1, (int) $query->fetchColumn());
    }

    public function testOrdersFromSamePhoneShareCustomer(): void
    {
        $business = $this->createBusiness(true, '+525500000000');
        $category = new Category();
        $category->businessId = (int) $business->id;
        $category->name = 'Food';
        (new CycleCategoryRepository())->create($category);
        $product = new Product();
        $product->businessId = (int) $business->id;
        $product->categoryId = (int) $category->id;
        $product->name = 'Taco';
        $product->createdAt = date(DATE_ATOM);
        (new CycleProductRepository())->create($product);
        $service = new OrderService(new CycleBusinessRepository(), new CycleProductRepository(), new CycleOrderRepository());
        $phone = '+5255' . random_int(10000000, 99999999);
        $input = ['phone' => $phone, 'fulfillment_type' => 'pickup', 'items' => [['product_id' => $product->id, 'quantity' => 1]]];
        $first = $service->create($business->slug, $input)['order'];
        $second = $service->create($business->slug, $input)['order'];
        self::assertNotNull($first->customerId);
        self::assertSame($first->customerId, $second->customerId);
        self::assertSame($phone, (new CycleCustomerRepository())->findByIdsForBusiness((int) $business->id, [$first->customerId])[$first->customerId]->phone);
    }

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

    public function testPublishedDirectoryFiltersAndExcludesUnpublishedBusinesses(): void
    {
        $location = 'DirectoryTest' . uniqid();
        $publishedCafe = $this->createBusiness(true, null, 'cafe', $location);
        $publishedStore = $this->createBusiness(true, null, 'store', 'Guadalajara');
        $unpublishedCafe = $this->createBusiness(false, null, 'cafe', 'Roma');
        $repository = new CycleBusinessRepository();
        $listedIds = array_map(static fn(Business $b) => (int) $b->id, $repository->findPublishedDirectory(null, null));
        self::assertContains((int) $publishedCafe->id, $listedIds);
        self::assertContains((int) $publishedStore->id, $listedIds);
        self::assertNotContains((int) $unpublishedCafe->id, $listedIds);
        $categoryIds = array_map(static fn(Business $b) => (int) $b->id, $repository->findPublishedDirectory('cafe', null));
        self::assertContains((int) $publishedCafe->id, $categoryIds);
        self::assertNotContains((int) $unpublishedCafe->id, $categoryIds);
        self::assertSame([(int) $publishedCafe->id], array_map(static fn(Business $b) => (int) $b->id, $repository->findPublishedDirectory(null, strtolower($location))));
        self::assertSame([(int) $publishedCafe->id], array_map(static fn(Business $b) => (int) $b->id, $repository->findPublishedDirectory('cafe', strtolower($location))));
    }

    public function testPublishedDirectorySortsByDistanceAndExcludesCoordinateLessBusinessesOnlyWhenRequested(): void
    {
        $category = 'geo-' . uniqid();
        $near = $this->createBusiness(true, null, $category, null); $near->latitude = 19.4326; $near->longitude = -99.1332;
        $far = $this->createBusiness(true, null, $category, null); $far->latitude = 20.6597; $far->longitude = -103.3496;
        $withoutCoordinates = $this->createBusiness(true, null, $category, null);
        $repository = new CycleBusinessRepository();
        $pdo = new \PDO(
            getenv('DATABASE_URL') ?: 'pgsql:host=postgres;port=5432;dbname=vendaly',
            getenv('POSTGRES_USER') ?: 'vendaly',
            getenv('POSTGRES_PASSWORD') ?: 'vendaly',
        );
        $statement = $pdo->prepare('UPDATE businesses SET latitude = :latitude, longitude = :longitude WHERE id = :id');
        $statement->execute(['latitude' => $near->latitude, 'longitude' => $near->longitude, 'id' => $near->id]);
        $statement->execute(['latitude' => $far->latitude, 'longitude' => $far->longitude, 'id' => $far->id]);
        $sorted = $repository->findPublishedDirectory($category, null, 19.4327, -99.1331);
        self::assertSame([(int) $near->id, (int) $far->id], array_map(static fn(Business $b) => (int) $b->id, $sorted));
        $base = $repository->findPublishedDirectory($category, null);
        $baseIds = array_map(static fn(Business $b) => (int) $b->id, $base);
        sort($baseIds);
        $expectedIds = [(int) $near->id, (int) $far->id, (int) $withoutCoordinates->id];
        sort($expectedIds);
        self::assertSame($expectedIds, $baseIds);
    }

    public function testPublishedDirectoryFiltersByPartialNameWithCategoryAndDistance(): void
    {
        $term = 'DirectoryName' . uniqid();
        $repository = new CycleBusinessRepository();
        $near = $this->createNamedBusiness($term . ' Near', true, 'cafe');
        $far = $this->createNamedBusiness($term . ' Far', true, 'cafe');
        $otherCategory = $this->createNamedBusiness($term . ' Store', true, 'store');
        $unpublished = $this->createNamedBusiness($term . ' Hidden', false, 'cafe');

        $pdo = new \PDO(
            getenv('DATABASE_URL') ?: 'pgsql:host=postgres;port=5432;dbname=vendaly',
            getenv('POSTGRES_USER') ?: 'vendaly',
            getenv('POSTGRES_PASSWORD') ?: 'vendaly',
        );
        $statement = $pdo->prepare('UPDATE businesses SET latitude = :latitude, longitude = :longitude WHERE id = :id');
        $statement->execute(['latitude' => 19.4326, 'longitude' => -99.1332, 'id' => $near->id]);
        $statement->execute(['latitude' => 20.6597, 'longitude' => -103.3496, 'id' => $far->id]);
        $statement->execute(['latitude' => 19.4328, 'longitude' => -99.1333, 'id' => $otherCategory->id]);

        $nameMatches = $repository->findPublishedDirectory(null, null, 19.4327, -99.1331, strtolower($term));
        self::assertSame([(int) $near->id, (int) $otherCategory->id, (int) $far->id], array_map(static fn(Business $b) => (int) $b->id, $nameMatches));
        self::assertNotContains((int) $unpublished->id, array_map(static fn(Business $b) => (int) $b->id, $nameMatches));

        $categoryMatches = $repository->findPublishedDirectory('cafe', null, null, null, $term);
        self::assertSame([(int) $near->id, (int) $far->id], array_map(static fn(Business $b) => (int) $b->id, $categoryMatches));
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

    public function testPaymentMethodsCreateFetchAndOrderByPosition(): void
    {
        $business = $this->createBusiness(); $repository = new CyclePaymentMethodRepository();
        $second = new PaymentMethod(); $second->businessId = (int) $business->id; $second->name = 'Tarjeta'; $second->position = 1; $repository->create($second);
        $first = new PaymentMethod(); $first->businessId = (int) $business->id; $first->name = 'Efectivo'; $first->position = 0; $repository->create($first);
        self::assertSame(['Efectivo', 'Tarjeta'], array_map(static fn(PaymentMethod $m): string => $m->name, $repository->findByBusinessId((int) $business->id)));
    }

    public function testProductOptionsReplaceAndFetchWithValues(): void
    {
        $business = $this->createBusiness();
        $category = new Category(); $category->businessId = (int) $business->id; $category->name = 'Food'; (new CycleCategoryRepository())->create($category);
        $product = new Product(); $product->businessId = (int) $business->id; $product->categoryId = (int) $category->id; $product->name = 'Coffee'; $product->createdAt = date(DATE_ATOM); (new CycleProductRepository())->create($product);
        $options = new CycleProductOptionRepository();
        $options->replaceForProduct((int) $product->id, [['name' => 'Sugar', 'selection_type' => 'single', 'required' => true, 'values' => [['name' => 'Brown sugar', 'price_delta' => '0']]]]);
        $found = $options->findByProductIds([(int) $product->id]);
        self::assertSame('Sugar', $found[$product->id][0]->name);
        self::assertSame('Brown sugar', $found[$product->id][0]->values[0]->name);
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

    public function testOrderNumbersAreSequentialPerBusiness(): void
    {
        $firstBusiness = $this->createBusiness();
        $secondBusiness = $this->createBusiness();
        $repository = new CycleOrderRepository();
        $orders = [];
        foreach ([$firstBusiness, $firstBusiness, $secondBusiness] as $business) {
            $order = new Order(); $order->businessId = (int) $business->id; $order->createdAt = date(DATE_ATOM); $repository->createWithItems($order, []); $orders[] = $order;
        }
        self::assertSame(1, $orders[0]->orderNumber);
        self::assertSame(2, $orders[1]->orderNumber);
        self::assertSame(1, $orders[2]->orderNumber);
    }

    public function testMigrationBackfillLeavesOrdersNumberedAndStatused(): void
    {
        $pdo = new \PDO(getenv('DATABASE_URL') ?: 'pgsql:host=postgres;port=5432;dbname=vendaly', getenv('POSTGRES_USER') ?: 'vendaly', getenv('POSTGRES_PASSWORD') ?: 'vendaly');
        self::assertSame(0, (int) $pdo->query('SELECT count(*) FROM orders WHERE order_number IS NULL OR status_id IS NULL')->fetchColumn());
        self::assertSame(0, (int) $pdo->query("SELECT count(*) FROM businesses b WHERE EXISTS (SELECT 1 FROM orders o WHERE o.business_id = b.id) AND EXISTS (SELECT 1 FROM (VALUES ('Creado'), ('Elaborando'), ('Entregado'), ('Cancelado')) v(name) WHERE NOT EXISTS (SELECT 1 FROM order_statuses s WHERE s.business_id = b.id AND s.name = v.name))")->fetchColumn());
    }

    public function testOrderItemOptionsArePersistedAsSnapshots(): void
    {
        $business = $this->createBusiness();
        $category = new Category(); $category->businessId = (int) $business->id; $category->name = 'Food'; (new CycleCategoryRepository())->create($category);
        $product = new Product(); $product->businessId = (int) $business->id; $product->categoryId = (int) $category->id; $product->name = 'Coffee'; $product->createdAt = date(DATE_ATOM); (new CycleProductRepository())->create($product);
        $order = new Order(); $order->businessId = (int) $business->id; $order->createdAt = date(DATE_ATOM);
        $item = new OrderItem(); $item->productId = (int) $product->id; $item->productNameSnapshot = 'Coffee'; $item->unitPriceSnapshot = '65.00';
        $option = new OrderItemOption(); $option->optionName = 'Add-on'; $option->valueName = 'Cream'; $option->priceDeltaSnapshot = '15.00';
        (new CycleOrderRepository())->createWithItems($order, [$item], [[$option]]);
        $pdo = new \PDO(getenv('DATABASE_URL') ?: 'pgsql:host=postgres;port=5432;dbname=vendaly', getenv('POSTGRES_USER') ?: 'vendaly', getenv('POSTGRES_PASSWORD') ?: 'vendaly');
        $row = $pdo->query('SELECT option_name, value_name, price_delta_snapshot FROM order_item_options WHERE order_item_id = ' . (int) $item->id)->fetch(\PDO::FETCH_ASSOC);
        self::assertSame(['option_name' => 'Add-on', 'value_name' => 'Cream', 'price_delta_snapshot' => '15.00'], $row);
    }

    public function testOrderListingFiltersInSqlAndIncludesItems(): void
    {
        $business = $this->createBusiness();
        $repository = new CycleOrderRepository();
        $category = new Category(); $category->businessId = (int) $business->id; $category->name = 'Food'; (new CycleCategoryRepository())->create($category);
        $product = new Product(); $product->businessId = (int) $business->id; $product->categoryId = (int) $category->id; $product->name = 'Burger'; $product->createdAt = date(DATE_ATOM); (new CycleProductRepository())->create($product);
        foreach (['2026-09-10T12:00:00+00:00', '2026-09-20T12:00:00+00:00', '2026-10-01T12:00:00+00:00'] as $date) {
            $order = new Order(); $order->businessId = (int) $business->id; $order->createdAt = $date; $repository->create($order);
            if ($date !== '2026-10-01T12:00:00+00:00') { $item = new \App\Domain\Entity\OrderItem(); $item->orderId = (int) $order->id; $item->productId = (int) $product->id; $item->productNameSnapshot = $date === '2026-09-20T12:00:00+00:00' ? 'Burger' : 'Fries'; $repository->createItem($item); }
        }
        $all = $repository->findByBusinessId((int) $business->id);
        self::assertCount(3, $all);
        self::assertCount(1, $all[1]['items']);
        self::assertCount(1, $all[2]['items']);
        $filtered = $repository->findByBusinessId((int) $business->id, '2026-09-15', '2026-09-30');
        self::assertCount(1, $filtered);
        self::assertSame('2026-09-20T12:00:00+00:00', $filtered[0]['order']->createdAt);
        self::assertSame('Burger', $filtered[0]['items'][0]->productNameSnapshot);
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

    private function createBusiness(bool $published = false, ?string $whatsappNumber = null, ?string $category = null, ?string $location = null): Business
    {
        $business = new Business();
        $business->id = $this->nextId('businesses');
        $business->ownerUserId = (int) $this->createUser()->id;
        $business->name = 'Test business';
        $business->slug = 'test-' . uniqid();
        $business->isPublished = $published;
        $business->whatsappNumber = $whatsappNumber;
        $business->category = $category;
        $business->location = $location;
        $business->createdAt = date(DATE_ATOM);
        return (new CycleBusinessRepository())->create($business);
    }

    private function createNamedBusiness(string $name, bool $published, string $category): Business
    {
        $business = new Business();
        $business->ownerUserId = (int) $this->createUser()->id;
        $business->id = $this->nextId('businesses');
        $business->name = $name;
        $business->slug = 'test-' . uniqid();
        $business->isPublished = $published;
        $business->category = $category;
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
