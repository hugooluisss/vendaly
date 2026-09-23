<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use DateTimeImmutable;

interface CatalogScanRepositoryInterface
{
    public function record(int $businessId): void;
    public function countTotal(int $businessId): int;
    /** @return list<array{date: string, count: int}> */
    public function countByDay(int $businessId, DateTimeImmutable $from, DateTimeImmutable $to): array;
}
