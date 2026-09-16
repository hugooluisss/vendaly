<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface ObjectStorageInterface
{
    public function put(string $key, string $contents, string $contentType): string;
}
