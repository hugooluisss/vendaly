import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { CartItem } from './public.models';
import { createOrderPayload } from './order-payload';

@Injectable({ providedIn: 'root' })
export class OrderApiService {
  private readonly http = inject(HttpClient);
  create(slug: string, items: CartItem[], total: number) {
    return this.http.post<{ whatsapp_url: string }>(`${environment.apiBaseUrl}/public/orders`, createOrderPayload(slug, items, total));
  }
}
