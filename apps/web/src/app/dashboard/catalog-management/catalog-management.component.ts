import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { BusinessApiService } from '../business-api.service';
import { CategoryApiService } from '../category-api.service';
import { ProductApiService, ProductInput } from '../product-api.service';
import { Category, Product, ProductOption } from '../catalog.models';
import { ModalComponent } from '../../shared/modal/modal.component';
import { ImageUploadComponent } from '../../shared/image-upload/image-upload.component';
import { NotificationService } from '../../shared/notification.service';

export function hasInvalidPricedOptions(product: ProductInput): boolean { return product.price == null && (product.options ?? []).some(option => option.values.some(value => Number(value.price_delta) !== 0)); }

@Component({ standalone: true, imports: [FormsModule, RouterLink, ModalComponent, ImageUploadComponent], templateUrl: './catalog-management.component.html', styleUrl: './catalog-management.component.css' })
export class CatalogManagementComponent {
  private readonly businessApi = inject(BusinessApiService); private readonly categoriesApi = inject(CategoryApiService); private readonly productsApi = inject(ProductApiService); readonly notification = inject(NotificationService);
  readonly coordinatesRequiredMessage = 'Agrega una ubicación en el mapa en Configuración del negocio antes de publicar.';
  businessId = 0; published = false; hasCoordinates = false; productError = ''; categories: Category[] = []; products: Product[] = []; newCategory = ''; editingCategory?: Category; editingProduct?: Product; image?: File; ingredientInput = '';
  product: ProductInput = { category_id: 0, name: '', description: '', price: null, is_active: true, ingredients: [], options: [] };
  modal: 'categories' | 'product' | 'confirm' | undefined; pendingDelete?: { type: 'category' | 'product'; name: string; id: number };

  constructor() { this.businessApi.getMine().subscribe(({ business }) => { this.businessId = business.id; this.published = !!business.is_published; this.hasCoordinates = business.latitude != null && business.longitude != null; this.load(); }); }
  load(): void { this.categoriesApi.list(this.businessId).subscribe(items => this.categories = items); this.productsApi.list(this.businessId).subscribe(items => this.products = items); }
  saveCategory(): void { if (!this.newCategory.trim()) return; const request = this.editingCategory ? this.categoriesApi.update(this.businessId, this.editingCategory.id, this.newCategory.trim()) : this.categoriesApi.create(this.businessId, this.newCategory.trim()); request.subscribe(() => { this.newCategory = ''; this.editingCategory = undefined; this.load(); }); }
  editCategory(category: Category): void { this.editingCategory = category; this.newCategory = category.name; }
  askDelete(type: 'category' | 'product', item: Category | Product): void { this.pendingDelete = { type, name: item.name, id: item.id }; this.modal = 'confirm'; }
  confirmDelete(): void { if (!this.pendingDelete) return; const { type, id } = this.pendingDelete; const request = type === 'category' ? this.categoriesApi.delete(this.businessId, id) : this.productsApi.delete(this.businessId, id); request.subscribe(() => { this.pendingDelete = undefined; this.modal = undefined; this.load(); }); }
  move(category: Category, direction: -1 | 1): void { const index = this.categories.indexOf(category); const next = index + direction; if (next < 0 || next >= this.categories.length) return; [this.categories[index], this.categories[next]] = [this.categories[next], this.categories[index]]; this.categoriesApi.reorder(this.businessId, this.categories.map(item => item.id)).subscribe(); }
  openNewProduct(): void { this.editingProduct = undefined; this.image = undefined; this.ingredientInput = ''; this.productError = ''; this.product = { category_id: this.categories[0]?.id ?? 0, name: '', description: '', price: null, is_active: true, ingredients: [], options: [] }; this.modal = 'product'; }
  addIngredient(): void { const ingredient = this.ingredientInput.trim(); if (!ingredient) return; this.product.ingredients = [...(this.product.ingredients ?? []), ingredient]; this.ingredientInput = ''; }
  removeIngredient(index: number): void { this.product.ingredients = (this.product.ingredients ?? []).filter((_, itemIndex) => itemIndex !== index); }
  saveProduct(): void { if (!this.product.name.trim() || !this.product.category_id) return; if (hasInvalidPricedOptions(this.product)) { this.productError = 'Los extras con precio requieren un producto con precio.'; return; } const request = this.editingProduct ? this.productsApi.update(this.businessId, this.editingProduct.id, this.product, this.image) : this.productsApi.create(this.businessId, this.product, this.image); request.subscribe(() => { this.resetProduct(); this.load(); }); }
  editProduct(item: Product): void { this.editingProduct = item; this.image = undefined; this.ingredientInput = ''; this.productError = ''; this.product = { category_id: item.category_id, name: item.name, description: item.description ?? '', price: item.price == null ? null : Number(item.price), is_active: item.is_active, ingredients: [...(item.ingredients ?? [])], options: structuredClone(item.options ?? []) }; this.modal = 'product'; }
  resetProduct(): void { this.editingProduct = undefined; this.image = undefined; this.ingredientInput = ''; this.productError = ''; this.product = { category_id: this.categories[0]?.id ?? 0, name: '', description: '', price: null, is_active: true, ingredients: [], options: [] }; this.modal = undefined; }
  closeModal(): void { this.modal = undefined; this.editingCategory = undefined; this.pendingDelete = undefined; }
  togglePublish(): void {
    if (!this.published && !this.hasCoordinates) { this.notification.error(this.coordinatesRequiredMessage); return; }
    this.businessApi.publish(this.businessId, !this.published).subscribe({
      next: () => this.published = !this.published,
      error: error => this.notification.error(error.error === 'Set a location on the map before publishing.' ? this.coordinatesRequiredMessage : error.error?.error ?? 'No se pudo actualizar la publicación del catálogo.'),
    });
  }
  onImageSelected(file: File): void { this.image = file; }
  addOption(): void { this.product.options = [...(this.product.options ?? []), { name: '', selection_type: 'single', required: false, values: [] }]; }
  removeOption(index: number): void { this.product.options = (this.product.options ?? []).filter((_, i) => i !== index); }
  addOptionValue(option: ProductOption): void { option.values = [...option.values, { name: '', price_delta: 0 }]; }
  removeOptionValue(option: ProductOption, index: number): void { option.values = option.values.filter((_, i) => i !== index); }
  moveOption(optionIndex: number, direction: -1 | 1): void { const options = [...(this.product.options ?? [])]; const next = optionIndex + direction; if (next < 0 || next >= options.length) return; [options[optionIndex], options[next]] = [options[next], options[optionIndex]]; this.product.options = options; }
  moveOptionValue(option: ProductOption, valueIndex: number, direction: -1 | 1): void { const next = valueIndex + direction; if (next < 0 || next >= option.values.length) return; [option.values[valueIndex], option.values[next]] = [option.values[next], option.values[valueIndex]]; }
}
