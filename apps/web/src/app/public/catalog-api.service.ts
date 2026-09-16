import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { PublicCatalog } from './public.models';

@Injectable({ providedIn: 'root' })
export class CatalogApiService {
  private readonly http = inject(HttpClient);
  get(slug: string) { return this.http.get<PublicCatalog>(`${environment.apiBaseUrl}/public/catalog/${encodeURIComponent(slug)}`); }
}
