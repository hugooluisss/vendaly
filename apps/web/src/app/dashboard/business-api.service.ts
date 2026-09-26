import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map } from 'rxjs';
import { environment } from '../../environments/environment';
import { Business, BusinessHour, BusinessSettings, FulfillmentMethod, OrderStatus, PaymentMethod } from './business.models';

@Injectable({ providedIn: 'root' })
export class BusinessApiService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiBaseUrl}/businesses`;

  create(name: string) { return this.http.post<{ business: Business }>(this.url, { name }).pipe(map(r => r.business)); }
  getMine() { return this.http.get<BusinessSettings>(`${this.url}/me`); }
  update(id: number, fields: Partial<Pick<Business, 'name' | 'whatsapp_number' | 'description' | 'category' | 'location' | 'latitude' | 'longitude' | 'wallet_enabled'>>, logo?: File, coverImage?: File) {
    if (!logo && !coverImage) return this.http.patch<{ business: Business }>(`${this.url}/${id}`, fields).pipe(map(r => r.business));
    const body = new FormData();
    Object.entries(fields).forEach(([key, value]) => body.append(key, String(value ?? '')));
    if (logo) body.append('logo', logo, logo.name);
    if (coverImage) body.append('cover_image', coverImage, coverImage.name);
    return this.http.post<{ business: Business }>(`${this.url}/${id}`, body).pipe(map(r => r.business));
  }
  paymentMethods(id: number) { return this.http.get<{ payment_methods: PaymentMethod[] }>(`${this.url}/${id}/payment-methods`).pipe(map(r => r.payment_methods)); }
  addPaymentMethod(id: number, name: string) { return this.http.post<{ payment_method: PaymentMethod }>(`${this.url}/${id}/payment-methods`, { name }).pipe(map(r => r.payment_method)); }
  renamePaymentMethod(id: number, methodId: number, name: string) { return this.http.patch<{ payment_method: PaymentMethod }>(`${this.url}/${id}/payment-methods/${methodId}`, { name }).pipe(map(r => r.payment_method)); }
  deletePaymentMethod(id: number, methodId: number) { return this.http.delete<void>(`${this.url}/${id}/payment-methods/${methodId}`); }
  fulfillmentMethods(id: number) { return this.http.get<{ fulfillment_methods: FulfillmentMethod[] }>(`${this.url}/${id}/fulfillment-methods`).pipe(map(r => r.fulfillment_methods)); }
  addFulfillmentMethod(id: number, name: string, fee?: number | null, requiresAddress = false) { return this.http.post<{ fulfillment_method: FulfillmentMethod }>(`${this.url}/${id}/fulfillment-methods`, { name, fee, requires_address: requiresAddress }).pipe(map(r => r.fulfillment_method)); }
  updateFulfillmentMethod(id: number, methodId: number, fields: Partial<Pick<FulfillmentMethod, 'name' | 'fee' | 'requires_address'>>) { return this.http.patch<{ fulfillment_method: FulfillmentMethod }>(`${this.url}/${id}/fulfillment-methods/${methodId}`, fields).pipe(map(r => r.fulfillment_method)); }
  deleteFulfillmentMethod(id: number, methodId: number) { return this.http.delete<void>(`${this.url}/${id}/fulfillment-methods/${methodId}`); }
  reorderPaymentMethods(id: number, ids: number[]) { return this.http.post<{ payment_methods: PaymentMethod[] }>(`${this.url}/${id}/payment-methods/reorder`, { positions: Object.fromEntries(ids.map((methodId, position) => [methodId, position])) }).pipe(map(r => r.payment_methods)); }
  orderStatuses(id: number) { return this.http.get<{ order_statuses: OrderStatus[] }>(`${this.url}/${id}/order-statuses`).pipe(map(r => r.order_statuses)); }
  addOrderStatus(id: number, name: string, color: string, isTerminal = false) { return this.http.post<{ order_status: OrderStatus }>(`${this.url}/${id}/order-statuses`, { name, color, is_terminal: isTerminal }).pipe(map(r => r.order_status)); }
  renameOrderStatus(id: number, statusId: number, name: string) { return this.http.patch<{ order_status: OrderStatus }>(`${this.url}/${id}/order-statuses/${statusId}`, { name }).pipe(map(r => r.order_status)); }
  recolorOrderStatus(id: number, statusId: number, color: string) { return this.http.patch<{ order_status: OrderStatus }>(`${this.url}/${id}/order-statuses/${statusId}`, { color }).pipe(map(r => r.order_status)); }
  setOrderStatusTerminal(id: number, statusId: number, isTerminal: boolean) { return this.http.patch<{ order_status: OrderStatus }>(`${this.url}/${id}/order-statuses/${statusId}`, { is_terminal: isTerminal }).pipe(map(r => r.order_status)); }
  setOrderStatusReversesWallet(id: number, statusId: number, reversesWallet: boolean) { return this.http.patch<{ order_status: OrderStatus }>(`${this.url}/${id}/order-statuses/${statusId}`, { reverses_wallet: reversesWallet }).pipe(map(r => r.order_status)); }
  deleteOrderStatus(id: number, statusId: number) { return this.http.delete<void>(`${this.url}/${id}/order-statuses/${statusId}`); }
  reorderOrderStatuses(id: number, ids: number[]) { return this.http.post<{ order_statuses: OrderStatus[] }>(`${this.url}/${id}/order-statuses/reorder`, { positions: Object.fromEntries(ids.map((statusId, position) => [statusId, position])) }).pipe(map(r => r.order_statuses)); }
  setOrderStatusDefault(id: number, statusId: number) { return this.http.post<{ order_status: OrderStatus }>(`${this.url}/${id}/order-statuses/${statusId}/default`, {}).pipe(map(r => r.order_status)); }
  saveHours(id: number, hours: BusinessHour[]) {
    return this.http.put<{ hours: BusinessHour[] }>(`${this.url}/${id}/hours`, { days: hours }).pipe(map(r => r.hours));
  }
  publish(id: number, published: boolean) {
    return this.http.post<{ business: Business }>(`${this.url}/${id}/publish`, { published }).pipe(map(r => r.business));
  }
}
