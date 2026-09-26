<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class DynamicFulfillmentMethods extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        $db->execute('CREATE TABLE fulfillment_methods (id BIGINT AUTO_INCREMENT PRIMARY KEY, business_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, fee DECIMAL(12,2) NULL, requires_address BOOLEAN NOT NULL DEFAULT FALSE, position INT NOT NULL DEFAULT 0, CONSTRAINT fulfillment_methods_business_fk FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE)');
        $db->execute("INSERT INTO fulfillment_methods (business_id, name, fee, requires_address, position) SELECT id, 'Recolección en tienda', pickup_fee, FALSE, 0 FROM businesses WHERE pickup_enabled = TRUE");
        $db->execute("INSERT INTO fulfillment_methods (business_id, name, fee, requires_address, position) SELECT id, 'Entrega a domicilio', delivery_fee, TRUE, 1 FROM businesses WHERE delivery_enabled = TRUE");
        $db->execute("INSERT INTO fulfillment_methods (business_id, name, fee, requires_address, position) SELECT id, 'Consumo en el local', dine_in_fee, FALSE, 2 FROM businesses WHERE dine_in_enabled = TRUE");
        $db->execute("INSERT INTO fulfillment_methods (business_id, name, fee, requires_address, position) SELECT b.id, 'Consumo en el local', NULL, FALSE, 0 FROM businesses b WHERE NOT EXISTS (SELECT 1 FROM fulfillment_methods fm WHERE fm.business_id = b.id)");
        $db->execute('ALTER TABLE orders ADD COLUMN fulfillment_method_id BIGINT NULL');
        $db->execute('ALTER TABLE orders ADD COLUMN fulfillment_method_snapshot VARCHAR(255) NULL');
        $db->execute("UPDATE orders o LEFT JOIN fulfillment_methods fm ON fm.business_id = o.business_id AND fm.name = CASE o.fulfillment_type WHEN 'pickup' THEN 'Recolección en tienda' WHEN 'delivery' THEN 'Entrega a domicilio' WHEN 'dine_in' THEN 'Consumo en el local' END SET o.fulfillment_method_id = fm.id, o.fulfillment_method_snapshot = CASE o.fulfillment_type WHEN 'pickup' THEN 'Recolección en tienda' WHEN 'delivery' THEN 'Entrega a domicilio' WHEN 'dine_in' THEN 'Consumo en el local' END");
        $db->execute('ALTER TABLE orders ADD CONSTRAINT orders_fulfillment_method_fk FOREIGN KEY (fulfillment_method_id) REFERENCES fulfillment_methods(id) ON DELETE SET NULL');
        $db->execute('ALTER TABLE businesses DROP COLUMN pickup_enabled, DROP COLUMN delivery_enabled, DROP COLUMN dine_in_enabled, DROP COLUMN pickup_fee, DROP COLUMN delivery_fee, DROP COLUMN dine_in_fee');
        $db->execute('ALTER TABLE orders DROP COLUMN fulfillment_type');
    }

    public function down(): void
    {
        $db = $this->database();
        $db->execute('ALTER TABLE orders DROP FOREIGN KEY orders_fulfillment_method_fk');
        $db->execute('ALTER TABLE orders DROP COLUMN fulfillment_method_id, DROP COLUMN fulfillment_method_snapshot');
        $db->execute('ALTER TABLE businesses ADD COLUMN pickup_enabled BOOLEAN NOT NULL DEFAULT TRUE, ADD COLUMN delivery_enabled BOOLEAN NOT NULL DEFAULT FALSE, ADD COLUMN dine_in_enabled BOOLEAN NOT NULL DEFAULT FALSE, ADD COLUMN pickup_fee DECIMAL(12,2) NULL, ADD COLUMN delivery_fee DECIMAL(12,2) NULL, ADD COLUMN dine_in_fee DECIMAL(12,2) NULL');
        $db->execute('ALTER TABLE orders ADD COLUMN fulfillment_type VARCHAR(32) NULL');
        $db->execute('DROP TABLE fulfillment_methods');
    }
}
