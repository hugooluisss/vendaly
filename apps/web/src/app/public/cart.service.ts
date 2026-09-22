import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { CartItem, PublicProduct } from './public.models';

@Injectable({ providedIn: 'root' })
export class CartService {
  private readonly itemsSubject = new BehaviorSubject<CartItem[]>(this.load());
  readonly items$ = this.itemsSubject.asObservable();
  get items(): CartItem[] { return this.itemsSubject.value; }
  add(product: PublicProduct, selectedOptionValueIds: number[] = []): void {
    const key = `${product.id}:${[...selectedOptionValueIds].sort((a, b) => a - b).join(',')}`;
    const existing = this.items.find(item => item.key === key);
    this.itemsSubject.next(existing ? this.items.map(item => item.key === key ? { ...item, quantity: item.quantity + 1 } : item) : [...this.items, { key, product, quantity: 1, note: '', selectedOptionValueIds }]);
    this.persist();
  }
  setQuantity(key: string | number, quantity: number): void {
    if (quantity <= 0) return this.remove(key);
    this.itemsSubject.next(this.items.map(item => item.key === String(key) || item.product.id === key ? { ...item, quantity } : item));
    this.persist();
  }
  setNote(key: string | number, note: string): void { this.itemsSubject.next(this.items.map(item => item.key === String(key) || item.product.id === key ? { ...item, note } : item)); this.persist(); }
  remove(key: string | number): void { this.itemsSubject.next(this.items.filter(item => item.key !== String(key) && item.product.id !== key)); this.persist(); }
  unitPrice(item: CartItem): number { return (Number(item.product.price) || 0) + (item.product.options ?? []).flatMap(option => option.values).filter(value => (item.selectedOptionValueIds ?? []).includes(value.id)).reduce((sum, value) => sum + Number(value.price_delta), 0); }
  selectedOptionNames(item: CartItem): string[] { return (item.product.options ?? []).flatMap(option => option.values).filter(value => (item.selectedOptionValueIds ?? []).includes(value.id)).map(value => value.name); }
  total(): number { return this.items.reduce((sum, item) => sum + this.unitPrice(item) * item.quantity, 0); }
  private load(): CartItem[] { try { const value = JSON.parse(localStorage.getItem('vendaly.cart') ?? 'null'); return Array.isArray(value) ? value : []; } catch { return []; } }
  private persist(): void { try { localStorage.setItem('vendaly.cart', JSON.stringify(this.items)); } catch {} }
}
