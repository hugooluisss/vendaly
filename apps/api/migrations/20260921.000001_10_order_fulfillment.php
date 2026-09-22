<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class OrderFulfillment extends Migration
{
    public function up(): void
    {
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS pickup_enabled BOOLEAN NOT NULL DEFAULT TRUE');
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS delivery_enabled BOOLEAN NOT NULL DEFAULT FALSE');
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS dine_in_enabled BOOLEAN NOT NULL DEFAULT FALSE');
        $this->database()->execute('ALTER TABLE orders ADD COLUMN IF NOT EXISTS fulfillment_type VARCHAR(16) NULL');
        $this->database()->execute('ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_address TEXT NULL');
        $this->database()->execute('ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_latitude DECIMAL(10,7) NULL');
        $this->database()->execute('ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_longitude DECIMAL(10,7) NULL');
    }

    public function down(): void
    {
        $this->database()->execute('ALTER TABLE orders DROP COLUMN IF EXISTS delivery_longitude');
        $this->database()->execute('ALTER TABLE orders DROP COLUMN IF EXISTS delivery_latitude');
        $this->database()->execute('ALTER TABLE orders DROP COLUMN IF EXISTS delivery_address');
        $this->database()->execute('ALTER TABLE orders DROP COLUMN IF EXISTS fulfillment_type');
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS dine_in_enabled');
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS delivery_enabled');
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS pickup_enabled');
    }
}
