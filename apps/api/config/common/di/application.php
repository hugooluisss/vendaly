<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Domain\Repository\{BusinessManagementRepositoryInterface, BusinessRepositoryInterface, CatalogScanRepositoryInterface, CategoryManagementRepositoryInterface, CategoryRepositoryInterface, CustomerRepositoryInterface, FulfillmentMethodRepositoryInterface, ObjectStorageInterface, OrderRepositoryInterface, OrderStatusRepositoryInterface, PaymentMethodRepositoryInterface, ProductIngredientRepositoryInterface, ProductManagementRepositoryInterface, ProductOptionRepositoryInterface, ProductRepositoryInterface, WalletRepositoryInterface};
use App\Infrastructure\Cycle\Repository\{CycleBusinessRepository, CycleCatalogScanRepository, CycleCategoryRepository, CycleCustomerRepository, CycleFulfillmentMethodRepository, CycleOrderRepository, CycleOrderStatusRepository, CyclePaymentMethodRepository, CycleProductIngredientRepository, CycleProductOptionRepository, CycleProductRepository, CycleWalletRepository};
use App\Infrastructure\Storage\S3CompatibleStorage;

/** @var array $params */

return [
    BusinessRepositoryInterface::class => CycleBusinessRepository::class,
    BusinessManagementRepositoryInterface::class => CycleBusinessRepository::class,
    CategoryRepositoryInterface::class => CycleCategoryRepository::class,
    CategoryManagementRepositoryInterface::class => CycleCategoryRepository::class,
    OrderRepositoryInterface::class => CycleOrderRepository::class,
    CustomerRepositoryInterface::class => CycleCustomerRepository::class,
    CatalogScanRepositoryInterface::class => CycleCatalogScanRepository::class,
    ProductRepositoryInterface::class => CycleProductRepository::class,
    ProductManagementRepositoryInterface::class => CycleProductRepository::class,
    ProductIngredientRepositoryInterface::class => CycleProductIngredientRepository::class,
    ProductOptionRepositoryInterface::class => CycleProductOptionRepository::class,
    PaymentMethodRepositoryInterface::class => CyclePaymentMethodRepository::class,
    FulfillmentMethodRepositoryInterface::class => CycleFulfillmentMethodRepository::class,
    OrderStatusRepositoryInterface::class => CycleOrderStatusRepository::class,
    WalletRepositoryInterface::class => CycleWalletRepository::class,
    ObjectStorageInterface::class => S3CompatibleStorage::class,
    ApplicationParams::class => [
        '__construct()' => [
            'name' => $params['application']['name'],
            'charset' => $params['application']['charset'],
            'locale' => $params['application']['locale'],
        ],
    ],
];
