<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\ProductIngredient;
use App\Domain\Repository\ProductIngredientRepositoryInterface;
use Cycle\ORM\EntityManager;

final class CycleProductIngredientRepository extends CycleRepository implements ProductIngredientRepositoryInterface
{
    public function replaceForProduct(int $productId, array $names): void
    {
        $this->orm->getSource(ProductIngredient::class)->getDatabase()->execute(
            'DELETE FROM product_ingredients WHERE product_id = ?',
            [$productId],
        );
        $manager = new EntityManager($this->orm);
        foreach (array_values($names) as $position => $name) {
            $ingredient = new ProductIngredient();
            $ingredient->productId = $productId;
            $ingredient->name = (string) $name;
            $ingredient->position = $position;
            $manager->persist($ingredient);
        }
        $manager->run();
    }

    public function findByProductIds(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }
        $ingredients = $this->orm->getRepository(ProductIngredient::class)->select()
            ->where(['productId' => ['IN' => array_values($productIds)]])
            ->orderBy('position')->fetchAll();
        $result = [];
        foreach ($ingredients as $ingredient) {
            $result[$ingredient->productId][] = $ingredient->name;
        }
        return $result;
    }
}
