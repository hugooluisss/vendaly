import { TestBed } from '@angular/core/testing';
import { ScanStatsService } from './scan-stats.service';

describe('ScanStatsService', () => {
  it('returns an empty result until scan tracking is available', (done) => {
    TestBed.inject(ScanStatsService).getStats(42).subscribe(stats => {
      expect(stats).toEqual({ total: 0, byDay: [] });
      done();
    });
  });
});
