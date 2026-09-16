<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'users')]
class User
{
    #[Column('primary')]
    public ?int $id = null;

    #[Column('string')]
    public string $email = '';

    #[Column('string', name: 'password_hash')]
    public string $passwordHash = '';

    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
}
