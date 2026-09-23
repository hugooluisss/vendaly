import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ScanStatsService } from './scan-stats.service';
import { environment } from '../../environments/environment';

describe('ScanStatsService', () => {
  let http: HttpTestingController;
  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [ScanStatsService, provideHttpClient(), provideHttpClientTesting()] });
    http = TestBed.inject(HttpTestingController);
  });
  afterEach(() => http.verify());

  it('maps the API by_day field to byDay', () => {
    let result: unknown;
    TestBed.inject(ScanStatsService).getStats(42).subscribe(stats => result = stats);
    const request = http.expectOne(`${environment.apiBaseUrl}/businesses/42/scans`);
    expect(request.request.method).toBe('GET');
    request.flush({ total: 3, by_day: [{ date: '2026-09-23', count: 3 }] });
    expect(result).toEqual({ total: 3, byDay: [{ date: '2026-09-23', count: 3 }] });
  });
});
