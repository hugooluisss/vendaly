import { AsyncPipe, CurrencyPipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { catchError, of, shareReplay } from 'rxjs';
import { ModalComponent } from '../../shared/modal/modal.component';
import { CartService } from '../cart.service';
import { CatalogApiService } from '../catalog-api.service';
import { PublicProduct } from '../public.models';

@Component({ standalone: true, imports: [AsyncPipe, CurrencyPipe, RouterLink, ModalComponent], templateUrl: './catalog-page.component.html', styleUrl: '../public.css' })
export class CatalogPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly catalogApi = inject(CatalogApiService);
  readonly cart = inject(CartService);
  readonly slug = this.route.snapshot.paramMap.get('slug') ?? '';
  readonly catalog$ = this.catalogApi.get(this.slug).pipe(catchError(() => of(null)), shareReplay(1));
  activeCategory: number | null = null;
  selections = new Map<number, number[]>();
  pendingProduct: PublicProduct | null = null;

  constructor() {
    if (this.route.snapshot.queryParamMap.get('src') === 'qr') {
      this.catalogApi.recordScan(this.slug).subscribe({ error: () => undefined });
    }
  }

  selectCategory(categoryId: number): void { this.activeCategory = categoryId; }
  whatsappLink(number: string): string { return 'https://wa.me/' + number.replace(/\D+/g, ''); }
  openOrder(): void { this.router.navigate(['/public/catalog', this.slug, 'order']); }
  addProduct(product: PublicProduct): void { if (product.options?.length) { this.pendingProduct = product; this.selections.set(product.id, []); } else this.cart.add(product); }
  confirmAdd(): void { if (this.pendingProduct && this.canAdd(this.pendingProduct)) { this.cart.add(this.pendingProduct, this.selected(this.pendingProduct)); this.closeModal(); } }
  closeModal(): void { if (this.pendingProduct) this.selections.delete(this.pendingProduct.id); this.pendingProduct = null; }
  selected(product: PublicProduct): number[] { return this.selections.get(product.id) ?? []; }
  toggle(product: PublicProduct, valueId: number, checked: boolean): void { const option = (product.options ?? []).find(group => group.values.some(value => value.id === valueId)); const values = option?.selection_type === 'single' ? (checked ? [valueId] : []) : this.selected(product).filter(id => id !== valueId); if (option?.selection_type !== 'single' && checked) values.push(valueId); this.selections.set(product.id, values); }
  canAdd(product: PublicProduct): boolean { return (product.options ?? []).every(option => !option.required || option.selection_type !== 'single' || option.values.some(value => this.selected(product).includes(value.id))); }
}
