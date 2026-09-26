<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\WalletTransaction;

interface WalletRepositoryInterface
{
    public function balanceForCustomer(int $customerId): string;
    public function postTransaction(int $customerId, ?int $orderId, string $type, string $amount): WalletTransaction;
    public function hasReversalForOrder(int $orderId): bool;
    /** @return WalletTransaction[] */
    public function originalTransactionsForOrder(int $orderId): array;
}
