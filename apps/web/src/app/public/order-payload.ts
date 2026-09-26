import { CartItem } from './public.models';
import { DeliveryLocation } from './delivery-location.service';

export function createOrderPayload(slug: string, items: CartItem[], total: number, fulfillmentMethodId: number, location: DeliveryLocation, paymentMethodId: number | null, phone: string, walletRedemption?: number | null) {
  return { slug, total, phone, ...(walletRedemption != null && walletRedemption > 0 ? { wallet_redemption: walletRedemption.toFixed(2) } : {}), fulfillment_method_id: fulfillmentMethodId, payment_method_id: paymentMethodId, delivery_address: location.address.trim() || null, delivery_latitude: location.position?.lat ?? null, delivery_longitude: location.position?.lng ?? null, items: items.map(({ product, quantity, note, selectedOptionValueIds }) => ({ product_id: product.id, quantity, note: note.trim() || null, option_value_ids: selectedOptionValueIds ?? [] })) };
}
