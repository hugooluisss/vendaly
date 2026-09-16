import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map } from 'rxjs';
import { environment } from '../../environments/environment';
import { Product } from './catalog.models';

export interface ProductInput {
  category_id: number;
  name: string;
  description?: string;
  price?: number | null;
  is_active?: boolean;
  ingredients?: string[];
}

export function productForm(input: ProductInput, image?: File): FormData {
  const body = new FormData();
  Object.entries(input).forEach(([key, value]) => {
    if (value !== undefined && value !== null) body.append(key, key === 'ingredients' ? JSON.stringify(value) : String(value));
  });
  if (image) body.append('image', image, image.name);
  return body;
}

@Injectable({ providedIn: 'root' })
export class ProductApiService {
  private readonly http = inject(HttpClient);
  private readonly url = (businessId: number) => `${environment.apiBaseUrl}/businesses/${businessId}/products`;

  list(businessId: number) { return this.http.get<{ products: Product[] }>(this.url(businessId)).pipe(map(r => r.products)); }
  create(businessId: number, input: ProductInput, image?: File) { return this.http.post<{ product: Product }>(this.url(businessId), productForm(input, image)).pipe(map(r => r.product)); }
  update(businessId: number, id: number, input: ProductInput, image?: File) { return image ? this.http.post<{ product: Product }>(`${this.url(businessId)}/${id}`, productForm(input, image)).pipe(map(r => r.product)) : this.http.patch<{ product: Product }>(`${this.url(businessId)}/${id}`, input).pipe(map(r => r.product)); }
  delete(businessId: number, id: number) { return this.http.delete<void>(`${this.url(businessId)}/${id}`); }
}
