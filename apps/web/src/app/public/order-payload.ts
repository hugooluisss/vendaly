import { CartItem } from './public.models';

export function createOrderPayload(slug: string, items: CartItem[], total: number) {
  return { slug, total, items: items.map(({ product, quantity, note }) => ({ product_id: product.id, quantity, note: note.trim() || null })) };
}
