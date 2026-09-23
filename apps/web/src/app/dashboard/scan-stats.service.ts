import { Injectable } from '@angular/core';
import { Observable, of } from 'rxjs';

export interface ScanStats {
  total: number;
  byDay: { date: string; count: number }[];
}

@Injectable({ providedIn: 'root' })
export class ScanStatsService {
  getStats(_businessId: number): Observable<ScanStats> {
    return of({ total: 0, byDay: [] });
  }
}
