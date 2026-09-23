<?php

declare(strict_types=1);

use App\Web;
use App\Web\Auth\{AccessTokenMiddleware, AuthController};
use App\Web\Business\BusinessController;
use App\Web\Catalog\CatalogController;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()
        ->routes(
            Route::get('/')
                ->action(Web\HomePage\Action::class)
                ->name('home'),
            Route::get('/health')
                ->action(Web\Health\Action::class)
                ->name('health'),
            Route::get('/public/catalog/{slug:[a-z0-9-]+}')->action(App\Web\PublicCatalog\Action::class)->name('public.catalog'),
            Route::get('/public/businesses')->action(App\Web\PublicBusinesses\Action::class)->name('public.businesses'),
            Route::post('/public/orders')->action(App\Web\PublicOrders\Action::class)->name('public.orders'),
            Route::post('/auth/register')
                ->action([AuthController::class, 'register'])
                ->name('auth.register'),
            Route::post('/auth/login')
                ->action([AuthController::class, 'login'])
                ->name('auth.login'),
            Route::post('/auth/refresh')
                ->action([AuthController::class, 'refresh'])
                ->name('auth.refresh'),
            Route::get('/auth/me')
                ->middleware(AccessTokenMiddleware::class)
                ->action(Web\Health\Action::class)
                ->name('auth.me'),
            Route::post('/businesses')
                ->middleware(AccessTokenMiddleware::class)
                ->action([BusinessController::class, 'create'])
                ->name('business.create'),
            Route::get('/businesses/me')
                ->middleware(AccessTokenMiddleware::class)
                ->action([BusinessController::class, 'me'])
                ->name('business.me'),
            Route::patch('/businesses/{id:\d+}')
                ->middleware(AccessTokenMiddleware::class)
                ->action([BusinessController::class, 'update'])
                ->name('business.update'),
            Route::post('/businesses/{id:\d+}')
                ->middleware(AccessTokenMiddleware::class)
                ->action([BusinessController::class, 'update'])
                ->name('business.update.multipart'),
            Route::put('/businesses/{id:\d+}/hours')
                ->middleware(AccessTokenMiddleware::class)
                ->action([BusinessController::class, 'hours'])
                ->name('business.hours'),
            Route::post('/businesses/{id:\d+}/publish')
                ->middleware(AccessTokenMiddleware::class)
                ->action([BusinessController::class, 'publish'])
                ->name('business.publish'),
            Route::get('/businesses/{id:\d+}/payment-methods')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'paymentMethods'])->name('payment-method.list'),
            Route::post('/businesses/{id:\d+}/payment-methods')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'createPaymentMethod'])->name('payment-method.create'),
            Route::patch('/businesses/{id:\d+}/payment-methods/{methodId:\d+}')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'updatePaymentMethod'])->name('payment-method.update'),
            Route::delete('/businesses/{id:\d+}/payment-methods/{methodId:\d+}')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'deletePaymentMethod'])->name('payment-method.delete'),
            Route::get('/businesses/{id:\d+}/order-statuses')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'orderStatuses'])->name('order-status.list'),
            Route::post('/businesses/{id:\d+}/order-statuses')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'createOrderStatus'])->name('order-status.create'),
            Route::patch('/businesses/{id:\d+}/order-statuses/{statusId:\d+}')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'updateOrderStatus'])->name('order-status.update'),
            Route::post('/businesses/{id:\d+}/order-statuses/reorder')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'reorderOrderStatuses'])->name('order-status.reorder'),
            Route::post('/businesses/{id:\d+}/order-statuses/{statusId:\d+}/default')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'setDefaultOrderStatus'])->name('order-status.default'),
            Route::delete('/businesses/{id:\d+}/order-statuses/{statusId:\d+}')->middleware(AccessTokenMiddleware::class)->action([BusinessController::class, 'deleteOrderStatus'])->name('order-status.delete'),
            Route::get('/businesses/{businessId:\d+}/categories')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'categories'])
                ->name('category.list'),
            Route::post('/businesses/{businessId:\d+}/categories')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'createCategory'])
                ->name('category.create'),
            Route::patch('/businesses/{businessId:\d+}/categories/{id:\d+}')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'updateCategory'])
                ->name('category.update'),
            Route::delete('/businesses/{businessId:\d+}/categories/{id:\d+}')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'deleteCategory'])
                ->name('category.delete'),
            Route::post('/businesses/{businessId:\d+}/categories/reorder')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'reorderCategories'])
                ->name('category.reorder'),
            Route::get('/businesses/{businessId:\d+}/products')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'products'])
                ->name('product.list'),
            Route::post('/businesses/{businessId:\d+}/products')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'createProduct'])
                ->name('product.create'),
            Route::patch('/businesses/{businessId:\d+}/products/{id:\d+}')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'updateProduct'])
                ->name('product.update'),
            Route::post('/businesses/{businessId:\d+}/products/{id:\d+}')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'updateProduct'])
                ->name('product.update.multipart'),
            Route::delete('/businesses/{businessId:\d+}/products/{id:\d+}')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'deleteProduct'])
                ->name('product.delete'),
            Route::post('/businesses/{businessId:\d+}/products/{id:\d+}/active')
                ->middleware(AccessTokenMiddleware::class)
                ->action([CatalogController::class, 'activeProduct'])
                ->name('product.active'),
            Route::get('/businesses/{businessId:\d+}/orders')
                ->middleware(AccessTokenMiddleware::class)
                ->action([App\Web\Orders\OrderController::class, 'list'])
                ->name('order.list'),
            Route::patch('/businesses/{businessId:\d+}/orders/{orderId:\d+}/status')
                ->middleware(AccessTokenMiddleware::class)
                ->action([App\Web\Orders\OrderController::class, 'changeStatus'])
                ->name('order.status'),
        ),
];
