<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface ProductOptionRepositoryInterface
{
    /** @param int[] $productIds @return array<int, \App\Domain\Entity\ProductOption[]> */
    public function findByProductIds(array $productIds): array;
    /** @param array<int, array{name:string,selection_type:string,required:bool,position?:int,values?:array}> $options */
    public function replaceForProduct(int $productId, array $options): void;
}
