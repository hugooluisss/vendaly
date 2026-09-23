import { TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { provideRouter } from '@angular/router';
import { of, throwError } from 'rxjs';
import { BusinessApiService } from '../business-api.service';
import { CategoryApiService } from '../category-api.service';
import { ProductApiService } from '../product-api.service';
import { Business } from '../business.models';
import { ScanStats, ScanStatsService } from '../scan-stats.service';
import { DashboardHomeComponent } from './dashboard-home.component';
import { QrCodeComponent } from '../../shared/qr-code/qr-code.component';

describe('DashboardHomeComponent', () => {
  function render(business: Business | undefined, stats: ScanStats = { total: 0, byDay: [] }) {
    const getStats = jasmine.createSpy('getStats').and.returnValue(of(stats));
    TestBed.configureTestingModule({ providers: [
      provideRouter([]),
      { provide: BusinessApiService, useValue: { getMine: () => business ? of({ business, hours: [] }) : throwError(() => new Error('No business')) } },
      { provide: CategoryApiService, useValue: { list: () => of([{ id: 1 }, { id: 2 }]) } },
      { provide: ProductApiService, useValue: { list: () => of([{ id: 1, is_active: true }, { id: 2, is_active: false }]) } },
      { provide: ScanStatsService, useValue: { getStats } },
    ] });
    const fixture = TestBed.createComponent(DashboardHomeComponent);
    fixture.detectChanges();
    return { fixture, getStats, element: fixture.nativeElement as HTMLElement };
  }

  it('shows the zero total and empty state when there are no daily scans', () => {
    const { element, getStats } = render({ id: 4, name: 'Tienda', is_published: false });
    expect(getStats).toHaveBeenCalledOnceWith(4);
    expect(element.querySelector('.scan-total')?.textContent).toContain('0');
    expect(element.querySelector('.scans-empty')?.textContent).toContain('Aún no hay escaneos');
    expect(element.querySelector('.chart')).toBeNull();
    expect([...element.querySelectorAll('.stat strong')].map(item => item.textContent)).toEqual(['2', '2', '1', '1']);
  });

  it('shows the total and per-day chart when scan data is available', () => {
    const { element } = render({ id: 4, name: 'Tienda', is_published: false }, {
      total: 3,
      byDay: [{ date: '2026-09-22', count: 2 }, { date: '2026-09-23', count: 1 }],
    });
    expect(element.querySelector('.scan-total')?.textContent).toContain('3');
    expect(element.querySelector('.scans-empty')).toBeNull();
    expect(element.querySelectorAll('.chart-day').length).toBe(2);
    expect(element.querySelector('.chart')?.textContent).toContain('2026-09-22');
  });

  it('keeps the onboarding prompt alone when there is no business', () => {
    const { element, getStats } = render(undefined);
    expect(element.textContent).toContain('Crear o configurar negocio');
    expect(element.querySelector('.business-header')).toBeNull();
    expect(element.querySelector('.qr-panel')).toBeNull();
    expect(element.querySelector('.stats')).toBeNull();
    expect(element.querySelector('.scans')).toBeNull();
    expect(getStats).not.toHaveBeenCalled();
  });

  it('does not show a QR for an unpublished business', () => {
    const { element } = render({ id: 4, name: 'Tienda', slug: 'tienda', is_published: false });
    expect(element.querySelector('.status')?.textContent).toContain('Sin publicar');
    expect(element.querySelector('.qr-panel app-qr-code')).toBeNull();
    expect(element.querySelector('.qr-panel')?.textContent).toContain('todavía no está publicado');
  });

  it('keeps the displayed catalog URL clean while adding the marker to the QR value', () => {
    const { fixture, element } = render({ id: 4, name: 'Tienda', slug: 'tienda', is_published: true });
    const component = fixture.componentInstance;
    expect(component.publicCatalogUrl).toContain('/public/catalog/tienda');
    expect(component.publicCatalogUrl).not.toContain('src=qr');
    expect(component.publicCatalogQrUrl).toBe(`${component.publicCatalogUrl}?src=qr`);
    expect(fixture.debugElement.query(By.directive(QrCodeComponent)).componentInstance.value).toBe(`${component.publicCatalogUrl}?src=qr`);
    expect(element.querySelector('.catalog-url')?.textContent?.trim()).toBe(component.publicCatalogUrl);
    expect(element.querySelector('.catalog-url')?.getAttribute('href')).toBe(component.publicCatalogUrl);
    fixture.destroy();
  });
});
