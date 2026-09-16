<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Domain\Repository\{BusinessManagementRepositoryInterface, BusinessRepositoryInterface, CategoryManagementRepositoryInterface, CategoryRepositoryInterface, ObjectStorageInterface, OrderRepositoryInterface, ProductIngredientRepositoryInterface, ProductManagementRepositoryInterface, ProductRepositoryInterface};
use App\Infrastructure\Cycle\Repository\{CycleBusinessRepository, CycleCategoryRepository, CycleOrderRepository, CycleProductIngredientRepository, CycleProductRepository};
use App\Infrastructure\Storage\S3CompatibleStorage;

/** @var array $params */

return [
    BusinessRepositoryInterface::class => CycleBusinessRepository::class,
    BusinessManagementRepositoryInterface::class => CycleBusinessRepository::class,
    CategoryRepositoryInterface::class => CycleCategoryRepository::class,
    CategoryManagementRepositoryInterface::class => CycleCategoryRepository::class,
    OrderRepositoryInterface::class => CycleOrderRepository::class,
    ProductRepositoryInterface::class => CycleProductRepository::class,
    ProductManagementRepositoryInterface::class => CycleProductRepository::class,
    ProductIngredientRepositoryInterface::class => CycleProductIngredientRepository::class,
    ObjectStorageInterface::class => S3CompatibleStorage::class,
    ApplicationParams::class => [
        '__construct()' => [
            'name' => $params['application']['name'],
            'charset' => $params['application']['charset'],
            'locale' => $params['application']['locale'],
        ],
    ],
];
