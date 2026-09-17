import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting, HttpTestingController } from '@angular/common/http/testing';
import { environment } from '../../environments/environment';
import { OrderApiService } from './order-api.service';

describe('OrderApiService', () => {
  let api: OrderApiService; let http: HttpTestingController;
  beforeEach(() => { TestBed.configureTestingModule({ providers: [OrderApiService, provideHttpClient(), provideHttpClientTesting()] }); api = TestBed.inject(OrderApiService); http = TestBed.inject(HttpTestingController); });
  afterEach(() => http.verify());
  it('lists orders with an optional date range', () => {
    api.list(4, '2026-09-10', '2026-09-16').subscribe();
    const req = http.expectOne(`${environment.apiBaseUrl}/businesses/4/orders?from=2026-09-10&to=2026-09-16`);
    expect(req.request.method).toBe('GET');
    req.flush({ orders: [], count: 0 });
  });
});
