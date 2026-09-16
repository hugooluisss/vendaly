<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Repository\BusinessRepositoryInterface;
use Endroid\QrCode\Builder\Builder;
use DomainException;
use Endroid\QrCode\Writer\SvgWriter;

final readonly class QrCodeService
{
    public function __construct(private BusinessRepositoryInterface $businesses)
    {
    }

    public function image(int $businessId, int $userId): string
    {
        $business = $this->businesses->findById($businessId);
        if ($business === null || $business->ownerUserId !== $userId || !$business->isPublished) {
            throw new DomainException('Not found.');
        }
        $url = rtrim(getenv('PUBLIC_CATALOG_BASE_URL') ?: 'http://localhost:4200/public/catalog', '/')
            . '/' . $business->slug;
        return (new Builder(writer: new SvgWriter(), data: $url))->build()->getString();
    }
}
