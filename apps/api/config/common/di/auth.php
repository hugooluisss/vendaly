<?php

declare(strict_types=1);
use App\Application\Auth\JwtService;
use App\Domain\Repository\{RefreshTokenRepositoryInterface, UserRepositoryInterface};
use App\Infrastructure\Cycle\Repository\{CycleRefreshTokenRepository, CycleUserRepository};
use App\Web\Auth\AccessTokenMiddleware;
use Yiisoft\Definitions\Reference;

return [
    UserRepositoryInterface::class => Reference::to(CycleUserRepository::class),
    RefreshTokenRepositoryInterface::class => Reference::to(CycleRefreshTokenRepository::class),
    JwtService::class => [
        '__construct()' => [
            'secret' => getenv('JWT_SECRET') ?: 'dev-only-change-me',
        ],
    ],
    AccessTokenMiddleware::class => [
        '__construct()' => [Reference::to(JwtService::class), Reference::to(UserRepositoryInterface::class)],
    ],
];
