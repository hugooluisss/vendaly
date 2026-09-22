import { CartItem } from './public.models';
import { DeliveryLocation } from './delivery-location.service';

export function createOrderPayload(slug: string, items: CartItem[], total: number, fulfillmentType: string, location: DeliveryLocation, paymentMethodId: number | null, phone: string) {
  return { slug, total, phone, fulfillment_type: fulfillmentType, payment_method_id: paymentMethodId, delivery_address: location.address.trim() || null, delivery_latitude: location.position?.lat ?? null, delivery_longitude: location.position?.lng ?? null, items: items.map(({ product, quantity, note, selectedOptionValueIds }) => ({ product_id: product.id, quantity, note: note.trim() || null, option_value_ids: selectedOptionValueIds ?? [] })) };
}
