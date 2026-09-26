<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Entity\{Business, Category, FulfillmentMethod, Order, OrderItem, OrderItemOption, OrderStatus, PaymentMethod, Product, ProductOption, ProductOptionValue, WalletTransaction};
use App\Domain\Repository\{BusinessRepositoryInterface, CatalogScanRepositoryInterface, CategoryRepositoryInterface, FulfillmentMethodRepositoryInterface, OrderRepositoryInterface, OrderStatusRepositoryInterface, PaymentMethodRepositoryInterface, ProductOptionRepositoryInterface, ProductRepositoryInterface, WalletRepositoryInterface};
use App\Domain\Service\{CatalogService, OrderService, WhatsAppOrderLink};
use DomainException;
use PHPUnit\Framework\TestCase;

final class PublicCatalogAndOrdersTest extends TestCase
{
    public function testCatalogScanServiceResolvesPublishedSlugAndReturnsStats(): void
    {
        $business = $this->business(true);
        $scans = new class implements CatalogScanRepositoryInterface {
            public array $recorded = [];
            public function record(int $businessId): void { $this->recorded[] = $businessId; }
            public function countTotal(int $businessId): int { return 3; }
            public function countByDay(int $businessId, \DateTimeImmutable $from, \DateTimeImmutable $to): array { return [['date' => '2026-09-23', 'count' => 3]]; }
        };
        $service = new CatalogService(new FakeBusinesses($business), new FakeCategories(), new FakeProducts(), scans: $scans);
        self::assertTrue($service->recordScan('shop'));
        self::assertSame([(int) $business->id], $scans->recorded);
        self::assertFalse((new CatalogService(new FakeBusinesses(null), new FakeCategories(), new FakeProducts(), scans: $scans))->recordScan('missing'));
        self::assertSame(['total' => 3, 'by_day' => [['date' => '2026-09-23', 'count' => 3]]], $service->scanStats((int) $business->id, new \DateTimeImmutable('2026-09-23'), new \DateTimeImmutable('2026-09-23')));
    }

    public function testOrderRequiresValidPhoneBeforePersistence(): void
    {
        $orders = new FakeOrders();
        $service = new OrderService(new FakeBusinesses($this->business(true)), new FakeProducts([$this->product(10, true, 'Taco')]), $orders, fulfillmentMethods: $this->defaultFulfillments());
        $input = ['fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 1]]];
        foreach ([$input, $input + ['phone' => 'bad-number']] as $invalid) {
            try {
                $service->create('shop', $invalid);
                self::fail('Expected phone validation.');
            } catch (DomainException $exception) {
                self::assertSame('Valid phone number is required.', $exception->getMessage());
            }
        }
        self::assertSame([], $orders->orders);
        self::assertNotNull($service->create('shop', $input + ['phone' => '+525512345678'])['order']);
        self::assertCount(1, $orders->orders);
    }

    public function testOrderCreationAssignsCurrentDefaultStatus(): void
    {
        $status = new OrderStatus(); $status->id = 9; $status->businessId = 1; $status->name = 'Elaborando'; $status->isDefault = true;
        $order = (new OrderService(new FakeBusinesses($this->business(true)), new FakeProducts([$this->product(10, true, 'Taco')]), new FakeOrders(), null, null, null, new PublicOrderStatuses([$status]), fulfillmentMethods: $this->defaultFulfillments()))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 1]]])['order'];
        self::assertSame(9, $order->statusId);
    }

    public function testWalletAccrualAndWhatsAppWalletLines(): void
    {
        $business = $this->business(true); $business->walletEnabled = true;
        $a = $this->product(10, true, 'A', '10'); $a->walletAmount = '5.00';
        $b = $this->product(11, true, 'B', '10'); $b->walletAmount = '10.00';
        $ledger = new FakeWallet();
        $orders = new FakeOrders(); $orders->wallet = $ledger;
        $service = new OrderService(new FakeBusinesses($business), new FakeProducts([$a, $b]), $orders, fulfillmentMethods: $this->defaultFulfillments(), wallet: $ledger);
        $data = $service->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 2], ['product_id' => 11, 'quantity' => 1]]]);
        self::assertSame('20.00', $data['wallet_credited']);
        self::assertSame('20.00', $data['wallet_balance']);
        self::assertSame('20.00', $ledger->balanceForCustomer(1));
        self::assertStringContainsString('Saldo acreditado: 20.00', rawurldecode((string) parse_url(WhatsAppOrderLink::generate($data['order'], $data['items'], $data['whatsapp_number'], true, '20.00', '0.00', '20.00'), PHP_URL_QUERY)));
        self::assertStringNotContainsString('Saldo acreditado', rawurldecode((string) parse_url(WhatsAppOrderLink::generate($data['order'], $data['items'], $data['whatsapp_number']), PHP_URL_QUERY)));
    }

    public function testWalletRedemptionValidationAndAdjustedOrderTotal(): void
    {
        $business = $this->business(true); $business->walletEnabled = true;
        $product = $this->product(10, true, 'Taco', '100');
        $ledger = new FakeWallet('40.00');
        $customers = new class implements \App\Domain\Repository\CustomerRepositoryInterface { public function findOrCreateByPhone(int $businessId, string $phone): \App\Domain\Entity\Customer { $c = new \App\Domain\Entity\Customer(); $c->id = 1; return $c; } public function findByIdsForBusiness(int $businessId, array $ids): array { return []; } };
        $orders = new FakeOrders(); $orders->wallet = $ledger;
        $service = new OrderService(new FakeBusinesses($business), new FakeProducts([$product]), $orders, customers: $customers, fulfillmentMethods: $this->defaultFulfillments(), wallet: $ledger);
        $data = $service->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'wallet_redemption' => 15, 'items' => [['product_id' => 10, 'quantity' => 1]]]);
        self::assertSame('85.00', $data['order']->total);
        self::assertSame('15.00', $data['wallet_redeemed']);
        self::assertSame('25.00', $data['wallet_balance']);
        foreach ([['wallet_redemption' => 41, 'message' => 'available balance'], ['wallet_redemption' => 101, 'message' => 'available balance']] as $case) {
            try {
                $service->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'wallet_redemption' => $case['wallet_redemption'], 'items' => [['product_id' => 10, 'quantity' => 1]]]);
                self::fail('Expected redemption rejection.');
            } catch (\DomainException $e) { self::assertStringContainsString($case['message'], $e->getMessage()); }
        }
    }

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
        self::assertArrayHasKey('cover_image_url', $result['business']);
        self::assertNull($result['business']['cover_image_url']);
        self::assertCount(1, $result['categories'][0]['products']);
        self::assertSame('Active', $result['categories'][0]['products'][0]['name']);
        self::assertSame(['Tomato'], $result['categories'][0]['products'][0]['ingredients']);
    }

    public function testSoftDeletedProductCannotCreateAnOrder(): void
    {
        $product = $this->product(10, true, 'Deleted');
        $product->deletedAt = date(DATE_ATOM);
        $service = new OrderService(new FakeBusinesses($this->business(true)), new FakeProducts([$product]), new FakeOrders(), fulfillmentMethods: $this->defaultFulfillments());
        $this->expectException(DomainException::class);
        $service->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 1]]]);
    }

    public function testUnknownAndUnpublishedCatalogsReturnNothing(): void
    {
        foreach ([$this->business(false), null] as $business) {
            self::assertNull((new CatalogService(new FakeBusinesses($business), new FakeCategories(), new FakeProducts(), new CatalogIngredients()))->publicCatalog('shop'));
        }
    }

    public function testPublicCatalogIncludesEmptyIngredientsAndBatchesOnce(): void
    {
        $b = $this->business(true);
        $c = new Category();
        $c->id = 4;
        $c->businessId = 1;
        $ingredients = new CatalogIngredients([10 => ['Tomato']]);
        $result = (new CatalogService(new FakeBusinesses($b), new FakeCategories([$c]), new FakeProducts([$this->product(10, true, 'A'), $this->product(11, true, 'B')]), $ingredients))->publicCatalog('shop');
        self::assertSame(['Tomato'], $result['categories'][0]['products'][0]['ingredients']);
        self::assertSame([], $result['categories'][0]['products'][1]['ingredients']);
        self::assertSame(1, $ingredients->loads);
    }

    public function testPublicCatalogIncludesCoverImageWhenSet(): void
    {
        $b = $this->business(true);
        $b->coverImageUrl = 'https://objects/businesses/1/cover';
        $result = (new CatalogService(new FakeBusinesses($b), new FakeCategories(), new FakeProducts()))->publicCatalog('shop');
        self::assertSame($b->coverImageUrl, $result['business']['cover_image_url']);
    }

    public function testPublicCatalogIncludesContactFieldsAndNulls(): void
    {
        $business = $this->business(true);
        $business->facebookUrl = 'https://facebook.com/shop';
        $business->instagramUrl = 'https://instagram.com/shop';
        $business->websiteUrl = 'https://shop.example';
        $result = (new CatalogService(new FakeBusinesses($business), new FakeCategories(), new FakeProducts()))->publicCatalog('shop');
        self::assertSame('+525512345678', $result['business']['whatsapp_number']);
        self::assertSame('https://facebook.com/shop', $result['business']['facebook_url']);
        self::assertSame('https://instagram.com/shop', $result['business']['instagram_url']);
        self::assertSame('https://shop.example', $result['business']['website_url']);

        $unset = (new CatalogService(new FakeBusinesses($this->business(true)), new FakeCategories(), new FakeProducts()))->publicCatalog('shop');
        foreach (['facebook_url', 'instagram_url', 'website_url'] as $field) self::assertNull($unset['business'][$field]);
        self::assertSame('+525512345678', $unset['business']['whatsapp_number']);
    }

    public function testPublicCatalogIncludesEmptyAndConfiguredOptions(): void
    {
        $category = new Category();
        $category->id = 4;
        $product = $this->product(10, true, 'Coffee', '50');
        $options = new CatalogOptions([10 => [$this->option('Sugar', 'single', true, [['Brown sugar', '0']])]]);
        $result = (new CatalogService(new FakeBusinesses($this->business(true)), new FakeCategories([$category]), new FakeProducts([$product]), null, $options))->publicCatalog('shop');
        self::assertSame('Sugar', $result['categories'][0]['products'][0]['options'][0]['name']);
        self::assertSame('Brown sugar', $result['categories'][0]['products'][0]['options'][0]['values'][0]['name']);
    }

    public function testOrderSnapshotsMixedPricesAndRejectsEmptyAndUnpublished(): void
    {
        $b = $this->business(true);
        $priced = $this->product(10, true, 'Changed later', '12.50');
        $free = $this->product(11, true, 'Service');
        $orders = new FakeOrders();
        $products = new FakeProducts([$priced, $free]);
        $service = new OrderService(new FakeBusinesses($b), $products, $orders, fulfillmentMethods: $this->defaultFulfillments());
        $saved = $service->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 2], ['product_id' => 11, 'quantity' => 1, 'note' => 'No onions']]]);
        self::assertSame('25.00', $saved['order']->total);
        self::assertSame('Changed later', $saved['items'][0]->productNameSnapshot);
        self::assertNull($saved['items'][1]->unitPriceSnapshot);
        self::assertSame(1, $products->bulkLoads);
        try {
            $service->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => []]);
            self::fail();
        } catch (DomainException) {
            self::assertCount(1, $orders->orders);
        }
        try {
            (new OrderService(new FakeBusinesses($this->business(false)), new FakeProducts([$priced]), new FakeOrders(), fulfillmentMethods: $this->defaultFulfillments()))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 1]]]);
            self::fail();
        } catch (DomainException) {
            self::assertTrue(true);
        }
    }

    public function testPersistenceFailureRollsBackOrderAndAllItems(): void
    {
        $orders = new FakeOrders(true);
        try {
            (new OrderService(new FakeBusinesses($this->business(true)), new FakeProducts([$this->product(10, true, 'Burger')]), $orders, fulfillmentMethods: $this->defaultFulfillments()))
                ->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 1]]]);
            self::fail('Expected persistence failure.');
        } catch (\RuntimeException) {
            self::assertCount(0, $orders->orders);
            self::assertCount(0, $orders->items);
        }
    }

    public function testOrderValidatesAndSnapshotsSelectedOptionsInAdjustedTotal(): void
    {
        $option = $this->option('Add-ons', 'multiple', false, [['Cream', '15']]);
        $options = new CatalogOptions([10 => [$option]]);
        $orders = new FakeOrders();
        $saved = (new OrderService(new FakeBusinesses($this->business(true)), new FakeProducts([$this->product(10, true, 'Coffee', '50')]), $orders, null, $options, fulfillmentMethods: $this->defaultFulfillments()))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 2, 'option_value_ids' => [101]]]]);
        self::assertSame('130.00', $saved['order']->total);
        self::assertSame('65.00', $saved['items'][0]->unitPriceSnapshot);
        self::assertSame('Cream', $saved['items'][0]->options[0]->valueName);
        $this->expectException(DomainException::class);
        (new OrderService(new FakeBusinesses($this->business(true)), new FakeProducts([$this->product(10, true, 'Coffee', '50')]), new FakeOrders(), null, $options, fulfillmentMethods: $this->defaultFulfillments()))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 1, 'option_value_ids' => [999]]]]);
    }

    public function testOrderRejectsMissingRequiredAndExtraSingleOption(): void
    {
        $options = new CatalogOptions([10 => [$this->option('Sugar', 'single', true, [['Brown', '0'], ['White', '0']])]]);
        $make = fn(array $ids) => (new OrderService(new FakeBusinesses($this->business(true)), new FakeProducts([$this->product(10, true, 'Coffee', '50')]), new FakeOrders(), null, $options, fulfillmentMethods: $this->defaultFulfillments()))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 1, 'items' => [['product_id' => 10, 'quantity' => 1, 'option_value_ids' => $ids]]]);
        try {
            $make([]);
            self::fail();
        } catch (DomainException) {
            self::assertTrue(true);
        }
        $this->expectException(DomainException::class);
        $make([101, 102]);
    }

    public function testWhatsAppLinkMixedItemsAndMissingNumber(): void
    {
        $order = new Order();
        $order->orderNumber = 17;
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
        self::assertStringContainsString(rawurlencode('Pedido #17'), $link);
        self::assertStringContainsString('wa.me/525512345678?text=', $link);
        self::assertStringContainsString(rawurlencode("* 2x Burger — 12.50\n* 1x Service (No onions)\n\nTotal: 25.00"), $link);
        $this->expectException(DomainException::class);
        WhatsAppOrderLink::generate($order, [$free], null);
    }

    public function testWhatsAppLinkIncludesSelectedOptionsAndDeltaAdjustedPrice(): void
    {
        $order = new Order();
        $order->total = '65.00';
        $item = new OrderItem();
        $item->quantity = 1;
        $item->productNameSnapshot = 'Coffee';
        $item->unitPriceSnapshot = '65.00';
        $option = new OrderItemOption();
        $option->optionName = 'Add-on';
        $option->valueName = 'Cream';
        $option->priceDeltaSnapshot = '15.00';
        $item->options = [$option];
        $link = WhatsAppOrderLink::generate($order, [$item], '+525500000000');
        self::assertStringContainsString(rawurlencode("* 1x Coffee [Add-on: Cream +15.00] — 65.00\n\nTotal: 65.00"), $link);
    }

    public function testCatalogAndOrdersUseFulfillmentMethodsAndDeliveryDetails(): void
    {
        $business = $this->business(true);
        $delivery = $this->fulfillment(2, 'Envío a domicilio', true);
        $dineIn = $this->fulfillment(3, 'Consumo en el local');
        $methods = new PublicFulfillmentMethods([$delivery, $dineIn]);
        $category = new Category();
        $category->id = 4;
        $catalog = (new CatalogService(new FakeBusinesses($business), new FakeCategories([$category]), new FakeProducts([$this->product(10, true, 'Taco')]), fulfillmentMethods: $methods))->publicCatalog('shop');
        self::assertSame([
            ['id' => 2, 'name' => 'Envío a domicilio', 'fee' => null, 'requires_address' => true],
            ['id' => 3, 'name' => 'Consumo en el local', 'fee' => null, 'requires_address' => false],
        ], $catalog['fulfillment_methods']);
        $orders = new FakeOrders();
        $saved = (new OrderService(new FakeBusinesses($business), new FakeProducts([$this->product(10, true, 'Taco')]), $orders, fulfillmentMethods: $methods))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 2, 'delivery_latitude' => 19.4, 'delivery_longitude' => -99.1, 'items' => [['product_id' => 10, 'quantity' => 1]]]);
        self::assertSame(2, $saved['order']->fulfillmentMethodId);
        self::assertSame('Envío a domicilio', $saved['order']->fulfillmentMethodSnapshot);
        self::assertSame(19.4, $saved['order']->deliveryLatitude);
        self::assertStringContainsString(rawurlencode("Envío a domicilio\nMapa: https://www.google.com/maps?q=19.4,-99.1"), WhatsAppOrderLink::generate($saved['order'], $saved['items'], $business->whatsappNumber));
        $delivery->name = 'Renamed after order';
        self::assertSame('Envío a domicilio', $saved['order']->fulfillmentMethodSnapshot);
        self::assertStringContainsString(rawurlencode('Envío a domicilio'), WhatsAppOrderLink::generate($saved['order'], $saved['items'], $business->whatsappNumber));
        foreach ([['phone' => '+525512345678'], ['phone' => '+525512345678', 'fulfillment_method_id' => 999], []] as $input) {
            try {
                (new OrderService(new FakeBusinesses($business), new FakeProducts([$this->product(10, true, 'Taco')]), new FakeOrders(), fulfillmentMethods: $methods))->create('shop', $input + ['items' => [['product_id' => 10, 'quantity' => 1]]]);
                self::fail('Expected fulfillment validation.');
            } catch (DomainException) {
                self::assertTrue(true);
            }
        }
    }

    public function testDeliveryAcceptsAddressOnlyAndWhatsAppListsEveryMethod(): void
    {
        $business = $this->business(true);
        $delivery = $this->fulfillment(2, 'Envío', true);
        $dineIn = $this->fulfillment(3, 'En el local');
        $methods = new PublicFulfillmentMethods([$this->fulfillment(1, 'Recolección'), $delivery, $dineIn]);
        $product = $this->product(10, true, 'Taco');
        try {
            (new OrderService(new FakeBusinesses($business), new FakeProducts([$product]), new FakeOrders(), fulfillmentMethods: $methods))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 2, 'items' => [['product_id' => 10, 'quantity' => 1]]]);
            self::fail('Address requiring methods need an address or complete coordinates.');
        } catch (DomainException $exception) {
            self::assertSame('Delivery requires an address or complete coordinates.', $exception->getMessage());
        }
        $pinOrder = (new OrderService(new FakeBusinesses($business), new FakeProducts([$product]), new FakeOrders(), fulfillmentMethods: $methods))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 2, 'delivery_latitude' => 19.4, 'delivery_longitude' => -99.1, 'items' => [['product_id' => 10, 'quantity' => 1]]])['order'];
        self::assertSame(19.4, $pinOrder->deliveryLatitude);
        $addressOrder = (new OrderService(new FakeBusinesses($business), new FakeProducts([$product]), new FakeOrders(), fulfillmentMethods: $methods))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 2, 'delivery_address' => 'Calle Roma 1', 'items' => [['product_id' => 10, 'quantity' => 1]]])['order'];
        self::assertSame('Calle Roma 1', $addressOrder->deliveryAddress);
        self::assertStringContainsString(rawurlencode("Envío\nDirección: Calle Roma 1"), WhatsAppOrderLink::generate($addressOrder, [], $business->whatsappNumber));
        foreach (['Recolección', $dineIn->name] as $label) {
            $order = new Order();
            $order->fulfillmentMethodSnapshot = $label;
            self::assertStringContainsString(rawurlencode($label), WhatsAppOrderLink::generate($order, [], $business->whatsappNumber));
        }
    }

    public function testPaymentMethodValidationFeeSnapshotAndWhatsAppBreakdown(): void
    {
        $business = $this->business(true);
        $delivery = $this->fulfillment(2, 'Entrega local', true, '30.00');
        $fulfillments = new PublicFulfillmentMethods([$delivery]);
        $method = new PaymentMethod(); $method->id = 8; $method->businessId = 1; $method->name = 'Transferencia';
        $orders = new FakeOrders();
        $saved = (new OrderService(new FakeBusinesses($business), new FakeProducts([$this->product(10, true, 'Taco', '100')]), $orders, null, null, new PublicPaymentMethods([$method]), fulfillmentMethods: $fulfillments))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 2, 'delivery_address' => 'Centro 1', 'payment_method_id' => 8, 'items' => [['product_id' => 10, 'quantity' => 1]]]);
        self::assertSame('130.00', $saved['order']->total);
        self::assertSame('30.00', $saved['order']->fulfillmentFeeSnapshot);
        self::assertSame('Transferencia', $saved['order']->paymentMethodSnapshot);
        $message = rawurldecode((string) parse_url(WhatsAppOrderLink::generate($saved['order'], $saved['items'], $business->whatsappNumber), PHP_URL_QUERY));
        self::assertStringContainsString('Subtotal: 100.00', $message);
        self::assertStringContainsString('Costo de entrega: 30.00', $message);
        self::assertStringContainsString('Método de pago: Transferencia', $message);
        $this->expectException(DomainException::class);
        (new OrderService(new FakeBusinesses($business), new FakeProducts([$this->product(10, true, 'Taco')]), new FakeOrders(), null, null, new PublicPaymentMethods([$method]), fulfillmentMethods: $fulfillments))->create('shop', ['phone' => '+525512345678', 'fulfillment_method_id' => 2, 'delivery_address' => 'Centro 1', 'items' => [['product_id' => 10, 'quantity' => 1]]]);
    }

    public function testCatalogIncludesFulfillmentFeesAndPaymentMethods(): void
    {
        $business = $this->business(true);
        $pickup = $this->fulfillment(1, 'Recolección', false, '5.00');
        $method = new PaymentMethod(); $method->id = 3; $method->name = 'Efectivo'; $method->position = 0;
        $catalog = (new CatalogService(new FakeBusinesses($business), new FakeCategories(), new FakeProducts(), paymentMethods: new PublicPaymentMethods([$method]), fulfillmentMethods: new PublicFulfillmentMethods([$pickup])))->publicCatalog('shop');
        self::assertSame('5.00', $catalog['fulfillment_methods'][0]['fee']);
        self::assertSame([['id' => 3, 'name' => 'Efectivo']], $catalog['payment_methods']);
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
    private function defaultFulfillments(): PublicFulfillmentMethods { return new PublicFulfillmentMethods([$this->fulfillment(1, 'Consumo en el local')]); }
    private function fulfillment(int $id, string $name, bool $requiresAddress = false, ?string $fee = null): FulfillmentMethod
    {
        $method = new FulfillmentMethod(); $method->id = $id; $method->businessId = 1; $method->name = $name; $method->requiresAddress = $requiresAddress; $method->fee = $fee; $method->position = $id - 1; return $method;
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
    private function option(string $name, string $type, bool $required, array $values): ProductOption
    {
        $option = new ProductOption();
        $option->id = 1;
        $option->name = $name;
        $option->selectionType = $type;
        $option->required = $required;
        foreach ($values as $index => [$valueName, $delta]) {
            $value = new ProductOptionValue();
            $value->id = 101 + $index;
            $value->name = $valueName;
            $value->priceDelta = $delta;
            $option->values[] = $value;
        } return $option;
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
    } public function findPublishedDirectory(?string $category, ?string $location, ?float $latitude = null, ?float $longitude = null, ?string $name = null): array
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
    public ?FakeWallet $wallet = null;
    public function __construct(private bool $fail = false) {} public function create(Order $entity): Order
    {
        $entity->id = count($this->orders) + 1;
        $this->orders[] = $entity;
        return $entity;
    } public function createWithItems(Order $entity, array $items, array $options = [], ?string $phone = null, string $walletCredit = '0.00', string $walletRedemption = '0.00'): Order
    {
        $entity->id = 1;
        $entity->customerId = 1;
        $this->orders[] = $entity;
        foreach ($items as $index => $item) {
            if ($this->fail && $index === 0) {
                $this->orders = [];
                $this->items = [];
                throw new \RuntimeException('forced failure');
            } $this->items[] = $item;
        }
        if ((float) $walletCredit > 0) $this->wallet?->postTransaction(1, (int) $entity->id, 'credit', $walletCredit);
        if ((float) $walletRedemption > 0) $this->wallet?->postTransaction(1, (int) $entity->id, 'debit', number_format(-abs((float) $walletRedemption), 2, '.', ''));
        return $entity;
    } public function createItem(OrderItem $entity): OrderItem
    {
        $this->items[] = $entity;
        return $entity;
    } public function findById(int $id): ?Order
    {
        return null;
    } public function findByBusinessId(int $businessId, ?string $from = null, ?string $to = null): array
    {
        return [];
    } public function findItems(int $orderId): array
    {
        return $this->items;
    }
}
final class FakeWallet implements WalletRepositoryInterface
{
    public array $transactions = [];
    public function __construct(private string $balance = '0.00') {}
    public function balanceForCustomer(int $customerId): string
    {
        $sum = (float) $this->balance;
        foreach ($this->transactions as $transaction) if ($transaction->customerId === $customerId) $sum += (float) $transaction->amount;
        return number_format($sum, 2, '.', '');
    }
    public function postTransaction(int $customerId, ?int $orderId, string $type, string $amount): WalletTransaction { $t = new WalletTransaction(); $t->id = count($this->transactions) + 1; $t->customerId = $customerId; $t->orderId = $orderId; $t->type = $type; $t->amount = $amount; $this->transactions[] = $t; return $t; }
    public function hasReversalForOrder(int $orderId): bool { foreach ($this->transactions as $t) if ($t->orderId === $orderId && $t->type === 'reversal') return true; return false; }
    public function originalTransactionsForOrder(int $orderId): array { return array_values(array_filter($this->transactions, static fn(WalletTransaction $t) => $t->orderId === $orderId && in_array($t->type, ['credit', 'debit'], true))); }
}
final class CatalogIngredients implements \App\Domain\Repository\ProductIngredientRepositoryInterface
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
final class CatalogOptions implements ProductOptionRepositoryInterface
{
    public function __construct(private array $items = []) {}
    public function replaceForProduct(int $productId, array $options): void {}
    public function findByProductIds(array $productIds): array
    {
        return array_intersect_key($this->items, array_flip($productIds));
    }
}

final class PublicOrderStatuses implements OrderStatusRepositoryInterface
{
    public function __construct(private array $items = []) {}
    public function findByBusinessId(int $businessId): array { return $this->items; }
    public function findById(int $id): ?OrderStatus { return null; }
    public function create(OrderStatus $status): OrderStatus { return $status; }
    public function update(OrderStatus $status): OrderStatus { return $status; }
    public function delete(OrderStatus $status): void {}
    public function existsOrderWithStatus(int $statusId): bool { return false; }
}

final class PublicPaymentMethods implements PaymentMethodRepositoryInterface
{
    public function __construct(private array $items = []) {}
    public function findByBusinessId(int $businessId): array { return array_values(array_filter($this->items, static fn(PaymentMethod $m): bool => $m->businessId === 0 || $m->businessId === $businessId)); }
    public function create(PaymentMethod $method): PaymentMethod { return $method; }
    public function update(PaymentMethod $method): PaymentMethod { return $method; }
    public function delete(PaymentMethod $method): void {}
}
final class PublicFulfillmentMethods implements FulfillmentMethodRepositoryInterface
{
    public function __construct(private array $items = []) {}
    public function findByBusinessId(int $businessId): array { return array_values(array_filter($this->items, static fn(FulfillmentMethod $method): bool => $method->businessId === $businessId)); }
    public function create(FulfillmentMethod $method): FulfillmentMethod { return $method; }
    public function update(FulfillmentMethod $method): FulfillmentMethod { return $method; }
    public function delete(FulfillmentMethod $method): void {}
}
