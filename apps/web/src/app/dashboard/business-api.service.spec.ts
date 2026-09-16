import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting, HttpTestingController } from '@angular/common/http/testing';
import { BusinessApiService } from './business-api.service';
import { environment } from '../../environments/environment';

describe('BusinessApiService', () => {
  let api: BusinessApiService; let http: HttpTestingController;
  beforeEach(() => { TestBed.configureTestingModule({ providers: [BusinessApiService, provideHttpClient(), provideHttpClientTesting()] }); api = TestBed.inject(BusinessApiService); http = TestBed.inject(HttpTestingController); });
  afterEach(() => http.verify());
  it('creates only with the business name', () => { api.create('Café').subscribe(); const req = http.expectOne(`${environment.apiBaseUrl}/businesses`); expect(req.request.method).toBe('POST'); expect(req.request.body).toEqual({ name: 'Café' }); });
  it('sends profile fields and logo as multipart data', () => { api.update(4, { name: 'X', whatsapp_number: '52155', description: '' }, new File(['x'], 'logo.png')).subscribe(); const req = http.expectOne(`${environment.apiBaseUrl}/businesses/4`); expect(req.request.method).toBe('POST'); expect(req.request.body instanceof FormData).toBeTrue(); expect((req.request.body as FormData).get('logo')).toBeTruthy(); });
  it('publishes with POST', () => { api.publish(4, true).subscribe(); const req = http.expectOne(`${environment.apiBaseUrl}/businesses/4/publish`); expect(req.request.method).toBe('POST'); });
});
