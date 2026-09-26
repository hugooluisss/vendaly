<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'wallet_transactions')]
class WalletTransaction
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'customer_id')]
    public int $customerId = 0;
    #[Column('integer', nullable: true, name: 'order_id')]
    public ?int $orderId = null;
    #[Column('string')]
    public string $type = '';
    #[Column('decimal')]
    public string $amount = '0.00';
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
}
