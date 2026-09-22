<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\{ProductOption, ProductOptionValue};
use App\Domain\Repository\ProductOptionRepositoryInterface;
use Cycle\ORM\EntityManager;

final class CycleProductOptionRepository extends CycleRepository implements ProductOptionRepositoryInterface
{
    public function replaceForProduct(int $productId, array $options): void
    {
        $database = $this->orm->getSource(ProductOption::class)->getDatabase();
        $database->execute('DELETE FROM product_options WHERE product_id = ?', [$productId]);
        $manager = new EntityManager($this->orm);
        foreach (array_values($options) as $position => $input) {
            $option = new ProductOption();
            $option->productId = $productId;
            $option->name = trim((string) ($input['name'] ?? ''));
            $option->selectionType = (string) ($input['selection_type'] ?? 'single');
            $option->required = $option->selectionType === 'single' && (bool) ($input['required'] ?? false);
            $option->position = (int) ($input['position'] ?? $position);
            $manager->persist($option)->run();
            foreach (array_values($input['values'] ?? []) as $valuePosition => $valueInput) {
                $value = new ProductOptionValue();
                $value->productOptionId = (int) $option->id;
                $value->name = trim((string) ($valueInput['name'] ?? ''));
                $value->priceDelta = (string) ($valueInput['price_delta'] ?? '0');
                $value->position = (int) ($valueInput['position'] ?? $valuePosition);
                $manager->persist($value);
            }
        }
        $manager->run();
    }

    public function findByProductIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }
        $options = $this->orm->getRepository(ProductOption::class)->select()->where(['productId' => ['IN' => array_values($productIds)]])->orderBy('position')->fetchAll();
        if ($options === []) {
            return [];
        }
        $values = $this->orm->getRepository(ProductOptionValue::class)->select()->where(['productOptionId' => ['IN' => array_map(static fn(ProductOption $option): int => (int) $option->id, $options)]])->orderBy('position')->fetchAll();
        $valuesByOption = [];
        foreach ($values as $value) {
            $valuesByOption[$value->productOptionId][] = $value;
        }
        $result = [];
        foreach ($options as $option) {
            $option->values = $valuesByOption[$option->id] ?? [];
            $result[$option->productId][] = $option;
        }
        return $result;
    }
}
