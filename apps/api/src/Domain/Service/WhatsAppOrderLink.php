<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\{Order, OrderItem};
use DomainException;

final class WhatsAppOrderLink
{
    /** @param OrderItem[] $items */
    public static function generate(Order $order, array $items, ?string $whatsappNumber): string
    {
        if ($whatsappNumber === null || trim($whatsappNumber) === '') {
            throw new DomainException('Business cannot currently receive orders.');
        }
        $lines = [];
        $hasPrice = false;
        foreach ($items as $item) {
            $line = $item->quantity . 'x ' . $item->productNameSnapshot;
            if ($item->unitPriceSnapshot !== null) {
                $line .= ' — ' . $item->unitPriceSnapshot;
                $hasPrice = true;
            }
            if ($item->note !== null && $item->note !== '') {
                $line .= ' (' . $item->note . ')';
            }
            $lines[] = $line;
        }
        if ($hasPrice && $order->total !== null) {
            $lines[] = '';
        }
        if ($hasPrice && $order->total !== null) {
            $lines[] = 'Total: ' . $order->total;
        }
        return 'https://wa.me/' . preg_replace('/\D+/', '', $whatsappNumber) . '?text=' . rawurlencode(implode("\n", $lines));
    }
}
