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
            Route::post('/public/orders')->action(App\Web\PublicOrders\Action::class)->name('public.orders'),
            Route::get('/businesses/{id:\d+}/qr')->action(App\Web\BusinessQr\Action::class)->name('business.qr'),
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
        ),
];
