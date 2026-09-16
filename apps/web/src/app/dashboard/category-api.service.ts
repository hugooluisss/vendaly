import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map } from 'rxjs';
import { environment } from '../../environments/environment';
import { Category } from './catalog.models';

@Injectable({ providedIn: 'root' })
export class CategoryApiService {
  private readonly http = inject(HttpClient);
  private readonly url = (businessId: number) => `${environment.apiBaseUrl}/businesses/${businessId}/categories`;

  list(businessId: number) { return this.http.get<{ categories: Category[] }>(this.url(businessId)).pipe(map(r => r.categories)); }
  create(businessId: number, name: string) { return this.http.post<{ category: Category }>(this.url(businessId), { name }).pipe(map(r => r.category)); }
  update(businessId: number, id: number, name: string) { return this.http.patch<{ category: Category }>(`${this.url(businessId)}/${id}`, { name }).pipe(map(r => r.category)); }
  delete(businessId: number, id: number) { return this.http.delete<void>(`${this.url(businessId)}/${id}`); }
  reorder(businessId: number, ids: number[]) { return this.http.post<{ categories: Category[] }>(`${this.url(businessId)}/reorder`, { positions: Object.fromEntries(ids.map((id, position) => [id, position])) }).pipe(map(r => r.categories)); }
}
