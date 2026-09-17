<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'refresh_tokens')]
class RefreshToken
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'user_id')]
    public int $userId = 0;
    #[Column('string', name: 'token_hash')]
    public string $tokenHash = '';
    #[Column('datetime', name: 'expires_at')]
    public string $expiresAt = '';
    #[Column('datetime', nullable: true, name: 'revoked_at')]
    public ?string $revokedAt = null;
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
}
