<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\{Order, OrderItem};
use DomainException;

final class WhatsAppOrderLink
{
    /** @param OrderItem[] $items */
    public static function generate(Order $order, array $items, ?string $whatsappNumber, bool $walletEnabled = false, string $walletCredited = '0.00', string $walletRedeemed = '0.00', ?string $walletBalance = null): string
    {
        if ($whatsappNumber === null || trim($whatsappNumber) === '') {
            throw new DomainException('Business cannot currently receive orders.');
        }
        $lines = [];
        if ($order->orderNumber !== null) {
            $lines[] = 'Pedido #' . $order->orderNumber;
            $lines[] = '';
        }
        $hasPrice = false;
        foreach ($items as $item) {
            $line = '* ' . $item->quantity . 'x ' . $item->productNameSnapshot;
            foreach ($item->options ?? [] as $option) {
                $line .= ' [' . $option->optionName . ': ' . $option->valueName;
                if ((float) $option->priceDeltaSnapshot !== 0.0) {
                    $line .= ' ' . ($option->priceDeltaSnapshot > 0 ? '+' : '') . $option->priceDeltaSnapshot;
                }
                $line .= ']';
            }
            if ($item->unitPriceSnapshot !== null) {
                $line .= ' — ' . $item->unitPriceSnapshot;
                $hasPrice = true;
            }
            if ($item->note !== null && $item->note !== '') {
                $line .= ' (' . $item->note . ')';
            }
            $lines[] = $line;
        }
        $fee = (float) ($order->fulfillmentFeeSnapshot ?? 0);
        if ($hasPrice && $order->total !== null && $fee === 0.0) {
            $lines[] = '';
        }
        if ($hasPrice && $order->total !== null && $fee === 0.0) {
            $lines[] = 'Total: ' . $order->total;
        }
        $lines[] = '';
        $lines[] = $order->fulfillmentMethodSnapshot ?? 'Método de entrega no especificado';
        if ($order->deliveryAddress !== null || $order->deliveryLatitude !== null) {
            if ($order->deliveryAddress !== null && trim($order->deliveryAddress) !== '') $lines[] = 'Dirección: ' . $order->deliveryAddress;
            if ($order->deliveryLatitude !== null && $order->deliveryLongitude !== null) $lines[] = 'Mapa: https://www.google.com/maps?q=' . $order->deliveryLatitude . ',' . $order->deliveryLongitude;
        }
        if ($order->paymentMethodSnapshot !== null) $lines[] = 'Método de pago: ' . $order->paymentMethodSnapshot;
        if ($fee !== 0.0 && $order->total !== null) {
            $lines[] = 'Subtotal: ' . number_format((float) $order->total - $fee, 2, '.', '');
            $lines[] = 'Costo de entrega: ' . number_format($fee, 2, '.', '');
            $lines[] = 'Total: ' . $order->total;
        }
        if ($walletEnabled) {
            $lines[] = '';
            $lines[] = 'Saldo acreditado: ' . $walletCredited;
            if ((float) $walletRedeemed > 0) $lines[] = 'Saldo utilizado: ' . $walletRedeemed;
            if ($walletBalance !== null) $lines[] = 'Saldo de monedero: ' . $walletBalance;
        }
        return 'https://wa.me/' . preg_replace('/\D+/', '', $whatsappNumber) . '?text=' . rawurlencode(implode("\n", $lines));
    }
}
