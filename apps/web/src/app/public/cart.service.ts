import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { CartItem, PublicProduct } from './public.models';

@Injectable({ providedIn: 'root' })
export class CartService {
  private readonly itemsSubject = new BehaviorSubject<CartItem[]>([]);
  readonly items$ = this.itemsSubject.asObservable();
  get items(): CartItem[] { return this.itemsSubject.value; }
  add(product: PublicProduct): void {
    const existing = this.items.find(item => item.product.id === product.id);
    this.itemsSubject.next(existing ? this.items.map(item => item.product.id === product.id ? { ...item, quantity: item.quantity + 1 } : item) : [...this.items, { product, quantity: 1, note: '' }]);
  }
  setQuantity(productId: number, quantity: number): void {
    if (quantity <= 0) return this.remove(productId);
    this.itemsSubject.next(this.items.map(item => item.product.id === productId ? { ...item, quantity } : item));
  }
  setNote(productId: number, note: string): void { this.itemsSubject.next(this.items.map(item => item.product.id === productId ? { ...item, note } : item)); }
  remove(productId: number): void { this.itemsSubject.next(this.items.filter(item => item.product.id !== productId)); }
  total(): number { return this.items.reduce((sum, item) => sum + (Number(item.product.price) || 0) * item.quantity, 0); }
}
