<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\CatalogScan;
use App\Domain\Repository\CatalogScanRepositoryInterface;
use DateTimeImmutable;

final class CycleCatalogScanRepository extends CycleRepository implements CatalogScanRepositoryInterface
{
    public function record(int $businessId): void
    {
        $this->orm->getSource(CatalogScan::class)->getDatabase()->execute(
            'INSERT INTO catalog_scans (business_id) VALUES (?)',
            [$businessId],
        );
    }

    public function countTotal(int $businessId): int
    {
        return (int) $this->orm->getSource(CatalogScan::class)->getDatabase()->query(
            'SELECT COUNT(*) FROM catalog_scans WHERE business_id = ?',
            [$businessId],
        )->fetchColumn();
    }

    public function countByDay(int $businessId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->orm->getSource(CatalogScan::class)->getDatabase()->query(
            "SELECT to_char(date_trunc('day', created_at), 'YYYY-MM-DD') AS date, COUNT(*) AS count
             FROM catalog_scans
             WHERE business_id = ? AND created_at >= ? AND created_at <= ?
             GROUP BY date_trunc('day', created_at)
             ORDER BY date_trunc('day', created_at)",
            [$businessId, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')],
        )->fetchAll();

        return array_map(static fn(array $row): array => [
            'date' => (string) $row['date'],
            'count' => (int) $row['count'],
        ], $rows);
    }
}
