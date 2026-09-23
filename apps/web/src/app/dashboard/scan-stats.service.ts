import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../environments/environment';

export interface ScanStats {
  total: number;
  byDay: { date: string; count: number }[];
}

@Injectable({ providedIn: 'root' })
export class ScanStatsService {
  private readonly http = inject(HttpClient);

  getStats(businessId: number): Observable<ScanStats> {
    return this.http.get<{ total: number; by_day: { date: string; count: number }[] }>(`${environment.apiBaseUrl}/businesses/${businessId}/scans`).pipe(
      map(response => ({ total: response.total, byDay: response.by_day })),
    );
  }
}
