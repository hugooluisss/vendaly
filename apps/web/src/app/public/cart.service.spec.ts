import { TestBed } from '@angular/core/testing';
import { CartService } from './cart.service';
import { PublicProduct } from './public.models';

const priced: PublicProduct = { id: 1, name: 'Torta', price: 80, ingredients: [] };
const priceless: PublicProduct = { id: 2, name: 'Servicio', price: null, ingredients: [] };

describe('CartService', () => {
  let cart: CartService;
  beforeEach(() => { localStorage.removeItem('vendaly.cart'); cart = TestBed.inject(CartService); });

  it('adds and increments an item', () => {
    cart.add(priced); cart.add(priced);
    expect(cart.items[0].quantity).toBe(2);
    expect(cart.total()).toBe(160);
  });

  it('changes quantity and removes items', () => {
    cart.add(priced); cart.setQuantity(1, 4);
    expect(cart.items[0].quantity).toBe(4);
    cart.setQuantity(1, 0);
    expect(cart.items).toEqual([]);
  });

  it('keeps priceless items but excludes them from the total', () => {
    cart.add(priced); cart.add(priceless); cart.setNote(2, 'A medida');
    expect(cart.items.length).toBe(2);
    expect(cart.items[1].note).toBe('A medida');
    expect(cart.total()).toBe(80);
  });

  it('includes selected option deltas and keeps different selections separate', () => {
    const product: PublicProduct = { id: 3, name: 'Café', price: 50, ingredients: [], options: [{ id: 1, name: 'Extra', selection_type: 'multiple', required: false, values: [{ id: 9, name: 'Crema', price_delta: 15 }] }] };
    cart.add(product, [9]); cart.add(product, []);
    expect(cart.items.length).toBe(2);
    expect(cart.total()).toBe(115);
  });

  it('persists mutations and hydrates a new instance', () => {
    cart.add(priced);
    expect(JSON.parse(localStorage.getItem('vendaly.cart')!)).toEqual(cart.items);
    expect(new CartService().items).toEqual(cart.items);
  });
});
