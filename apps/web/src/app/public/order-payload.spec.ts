import { createOrderPayload } from './order-payload';
import { CartItem } from './public.models';

describe('createOrderPayload', () => {
  it('maps the selection to the order contract', () => {
    const items: CartItem[] = [{ product: { id: 7, name: 'Taco', price: 25, ingredients: [] }, quantity: 2, note: ' sin cebolla ' }];
    expect(createOrderPayload('taqueria', items, 50)).toEqual({
      slug: 'taqueria', total: 50, items: [{ product_id: 7, quantity: 2, note: 'sin cebolla' }],
    });
  });
});
