import { Location } from '@angular/common';
import { Component, ViewChild, inject } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { BusinessApiService } from '../business-api.service';
import { CategoryApiService } from '../category-api.service';
import { ProductApiService } from '../product-api.service';
import { Business } from '../business.models';
import { QrCodeComponent } from '../../shared/qr-code/qr-code.component';
import { ScanStatsService } from '../scan-stats.service';

@Component({ standalone: true, imports: [RouterLink, QrCodeComponent], styleUrl: './dashboard-home.component.css', templateUrl: './dashboard-home.component.html' })
export class DashboardHomeComponent {
  private readonly businessApi = inject(BusinessApiService);
  private readonly categoriesApi = inject(CategoryApiService);
  private readonly productsApi = inject(ProductApiService);
  private readonly scanStats = inject(ScanStatsService);
  private readonly location = inject(Location);
  private readonly router = inject(Router);
  @ViewChild(QrCodeComponent) qrCode?: QrCodeComponent;
  business?: Business;
  publicCatalogUrl?: string;
  categoryCount = 0; productCount = 0; activeProducts = 0; inactiveProducts = 0;
  scanTotal = 0;
  scanByDay: { date: string; count: number }[] = [];
  get hasScans(): boolean { return this.scanByDay.length > 0; }

  constructor() {
    this.businessApi.getMine().subscribe({ next: ({ business }) => {
      this.business = business;
      this.publicCatalogUrl = business.is_published && business.slug
        ? window.location.origin + this.location.prepareExternalUrl(this.router.serializeUrl(this.router.createUrlTree(['/public/catalog', business.slug])))
        : undefined;
      this.categoriesApi.list(business.id).subscribe(items => this.categoryCount = items.length);
      this.productsApi.list(business.id).subscribe(items => {
        this.productCount = items.length;
        this.activeProducts = items.filter(item => item.is_active).length;
        this.inactiveProducts = items.length - this.activeProducts;
      });
      this.scanStats.getStats(business.id).subscribe({
        next: stats => { this.scanTotal = stats.total; this.scanByDay = stats.byDay; },
        error: () => { this.scanTotal = 0; this.scanByDay = []; },
      });
    }, error: () => this.business = undefined });
  }

  downloadQr(): void { this.qrCode?.download('catalogo-qr.png'); }
  scanBarHeight(count: number): number {
    const max = Math.max(...this.scanByDay.map(day => day.count));
    return max > 0 ? Math.max(0, count) / max * 100 : 0;
  }
}
