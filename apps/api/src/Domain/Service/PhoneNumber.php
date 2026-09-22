<?php

declare(strict_types=1);

namespace App\Domain\Service;

final class PhoneNumber
{
    public const PATTERN = '/^\+?[1-9][0-9 ()-]{6,20}$/';

    public static function isValid(string $phone): bool
    {
        return preg_match(self::PATTERN, $phone) === 1;
    }
}
