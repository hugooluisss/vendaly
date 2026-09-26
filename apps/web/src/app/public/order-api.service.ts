import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { CartItem } from './public.models';
import { createOrderPayload } from './order-payload';
import { DeliveryLocation } from './delivery-location.service';

export interface PublicOrderResponse { whatsapp_url: string; wallet_enabled?: boolean; wallet_credited?: string; wallet_redeemed?: string; wallet_balance?: string | null; }

@Injectable({ providedIn: 'root' })
export class OrderApiService {
  private readonly http = inject(HttpClient);
  create(slug: string, items: CartItem[], total: number, fulfillmentMethodId: number, location: DeliveryLocation, paymentMethodId: number | null, phone: string, walletRedemption?: number | null) {
    return this.http.post<PublicOrderResponse>(`${environment.apiBaseUrl}/public/orders`, createOrderPayload(slug, items, total, fulfillmentMethodId, location, paymentMethodId, phone, walletRedemption));
  }
}
