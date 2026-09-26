<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class CatalogScans extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        $db->execute('CREATE TABLE catalog_scans (id BIGINT AUTO_INCREMENT PRIMARY KEY, business_id BIGINT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT catalog_scans_business_fk FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE)');
        $db->execute('CREATE INDEX catalog_scans_business_id_index ON catalog_scans (business_id)');
    }

    public function down(): void
    {
        $db = $this->database();
        $db->execute('DROP TABLE catalog_scans');
    }
}
