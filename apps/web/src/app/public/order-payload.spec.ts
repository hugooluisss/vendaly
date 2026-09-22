import { createOrderPayload } from './order-payload';
import { CartItem } from './public.models';

describe('createOrderPayload', () => {
  it('maps the selection to the order contract', () => {
    const items: CartItem[] = [{ product: { id: 7, name: 'Taco', price: 25, ingredients: [] }, quantity: 2, note: ' sin cebolla ', selectedOptionValueIds: [4] }];
    expect(createOrderPayload('taqueria', items, 50, 'delivery', { address: ' Roma ', position: { lat: 19.4, lng: -99.1 } }, 12, '+52 55 1234 5678')).toEqual({
      slug: 'taqueria', total: 50, phone: '+52 55 1234 5678', fulfillment_type: 'delivery', payment_method_id: 12, delivery_address: 'Roma', delivery_latitude: 19.4, delivery_longitude: -99.1, items: [{ product_id: 7, quantity: 2, note: 'sin cebolla', option_value_ids: [4] }],
    });
  });
});
