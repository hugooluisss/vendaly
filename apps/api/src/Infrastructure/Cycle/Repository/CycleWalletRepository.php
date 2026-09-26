<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\WalletTransaction;
use App\Domain\Repository\WalletRepositoryInterface;

final class CycleWalletRepository extends CycleRepository implements WalletRepositoryInterface
{
    public function balanceForCustomer(int $customerId): string
    {
        $value = $this->orm->getSource(WalletTransaction::class)->getDatabase()
            ->query('SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE customer_id = ?', [$customerId])->fetchColumn();
        return number_format((float) $value, 2, '.', '');
    }

    public function postTransaction(int $customerId, ?int $orderId, string $type, string $amount): WalletTransaction
    {
        $transaction = new WalletTransaction();
        $transaction->customerId = $customerId;
        $transaction->orderId = $orderId;
        $transaction->type = $type;
        $transaction->amount = number_format((float) $amount, 2, '.', '');
        $transaction->createdAt = date(DATE_ATOM);
        return $this->save($transaction);
    }

    public function hasReversalForOrder(int $orderId): bool
    {
        return $this->orm->getSource(WalletTransaction::class)->getDatabase()
            ->query("SELECT 1 FROM wallet_transactions WHERE order_id = ? AND type = 'reversal' LIMIT 1", [$orderId])->fetch() !== false;
    }

    public function originalTransactionsForOrder(int $orderId): array
    {
        return $this->orm->getRepository(WalletTransaction::class)->select()
            ->where(['orderId' => $orderId, 'type' => ['IN' => ['credit', 'debit']]])->fetchAll();
    }
}
