import { HttpClient } from '@angular/common/http';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { AuthService } from './auth.service';
import { authInterceptor } from './auth.interceptor';

describe('authInterceptor', () => {
  let http: HttpClient;
  let requests: HttpTestingController;
  let router: jasmine.SpyObj<Router>;

  beforeEach(() => {
    localStorage.clear();
    router = jasmine.createSpyObj('Router', ['navigate', 'createUrlTree']);
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(withInterceptors([authInterceptor])),
        provideHttpClientTesting(),
        { provide: Router, useValue: router }
      ]
    });
    http = TestBed.inject(HttpClient);
    requests = TestBed.inject(HttpTestingController);
    localStorage.setItem('vendaly.auth.tokens', JSON.stringify({ accessToken: 'old', refreshToken: 'refresh' }));
  });

  afterEach(() => {
    requests.verify();
    localStorage.clear();
  });

  it('adds the access token to API requests', () => {
    http.get('http://localhost:8080/business').subscribe();

    const request = requests.expectOne('http://localhost:8080/business');
    expect(request.request.headers.get('Authorization')).toBe('Bearer old');
    request.flush({ ok: true });
  });

  it('refreshes once after 401 and retries the original request', () => {
    http.get('http://localhost:8080/business').subscribe(response => expect(response).toEqual({ ok: true }));

    const original = requests.expectOne('http://localhost:8080/business');
    original.flush({}, { status: 401, statusText: 'Unauthorized' });
    const refresh = requests.expectOne('http://localhost:8080/auth/refresh');
    expect(refresh.request.body).toEqual({ refresh_token: 'refresh' });
    refresh.flush({ access_token: 'new', refresh_token: 'rotated' });

    const retry = requests.expectOne('http://localhost:8080/business');
    expect(retry.request.headers.get('Authorization')).toBe('Bearer new');
    retry.flush({ ok: true });
  });

  it('shares one refresh request across concurrent 401 responses', () => {
    const responses: unknown[] = [];
    http.get('http://localhost:8080/categories').subscribe(response => responses.push(response));
    http.get('http://localhost:8080/products').subscribe(response => responses.push(response));

    requests.expectOne('http://localhost:8080/categories').flush({}, { status: 401, statusText: 'Unauthorized' });
    requests.expectOne('http://localhost:8080/products').flush({}, { status: 401, statusText: 'Unauthorized' });

    const refresh = requests.expectOne('http://localhost:8080/auth/refresh');
    expect(refresh.request.body).toEqual({ refresh_token: 'refresh' });
    refresh.flush({ access_token: 'new', refresh_token: 'rotated' });

    const categoryRetry = requests.expectOne('http://localhost:8080/categories');
    const productRetry = requests.expectOne('http://localhost:8080/products');
    expect(categoryRetry.request.headers.get('Authorization')).toBe('Bearer new');
    expect(productRetry.request.headers.get('Authorization')).toBe('Bearer new');
    categoryRetry.flush({ items: ['category'] });
    productRetry.flush({ items: ['product'] });

    expect(responses).toEqual([{ items: ['category'] }, { items: ['product'] }]);
  });
});
