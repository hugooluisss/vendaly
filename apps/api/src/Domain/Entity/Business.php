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
    #[Column('string', nullable: true, name: 'cover_image_url')]
    public ?string $coverImageUrl = null;
    #[Column('string', nullable: true, name: 'whatsapp_number')]
    public ?string $whatsappNumber = null;
    #[Column('string', nullable: true, name: 'facebook_url')]
    public ?string $facebookUrl = null;
    #[Column('string', nullable: true, name: 'instagram_url')]
    public ?string $instagramUrl = null;
    #[Column('string', nullable: true, name: 'website_url')]
    public ?string $websiteUrl = null;
    #[Column('text', nullable: true)]
    public ?string $description = null;
    #[Column('text', nullable: true)]
    public ?string $category = null;
    #[Column('text', nullable: true)]
    public ?string $location = null;
    #[Column('decimal', nullable: true, typecast: 'float')]
    public ?float $latitude = null;
    #[Column('decimal', nullable: true, typecast: 'float')]
    public ?float $longitude = null;
    #[Column('boolean', name: 'is_published', default: false, typecast: 'bool')]
    public bool $isPublished = false;
    #[Column('boolean', name: 'wallet_enabled', default: false, typecast: 'bool')]
    public bool $walletEnabled = false;
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
    #[Column('integer', name: 'next_order_number', default: 1)]
    public int $nextOrderNumber = 1;
}
