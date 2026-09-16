<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'businesses')]
class Business
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'owner_user_id')]
    public int $ownerUserId = 0;
    #[Column('string')]
    public string $name = '';
    #[Column('string')]
    public string $slug = '';
    #[Column('string', nullable: true, name: 'logo_url')]
    public ?string $logoUrl = null;
    #[Column('string', nullable: true, name: 'whatsapp_number')]
    public ?string $whatsappNumber = null;
    #[Column('text', nullable: true)]
    public ?string $description = null;
    #[Column('boolean', name: 'is_published', default: false)]
    public bool $isPublished = false;
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
}
