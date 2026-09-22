import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { OrderList } from './order.models';

@Injectable({ providedIn: 'root' })
export class OrderApiService {
  private readonly http = inject(HttpClient);

  list(businessId: number, from?: string, to?: string) {
    let params = new HttpParams();
    if (from) params = params.set('from', from);
    if (to) params = params.set('to', to);
    return this.http.get<OrderList>(`${environment.apiBaseUrl}/businesses/${businessId}/orders`, { params });
  }

  updateStatus(businessId: number, orderId: number, statusId: number) {
    return this.http.patch<void>(`${environment.apiBaseUrl}/businesses/${businessId}/orders/${orderId}/status`, { status_id: statusId });
  }
}
