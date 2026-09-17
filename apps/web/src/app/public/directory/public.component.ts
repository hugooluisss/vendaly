import { AfterViewInit, Component, ElementRef, inject, OnDestroy, ViewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BusinessDirectoryApiService } from '../business-directory-api.service';
import { DirectoryBusiness } from '../public.models';
import { BUSINESS_CATEGORIES, businessCategoryLabel } from '../../shared/business-category';
import { MapComponent, MapPosition } from '../../shared/map/map.component';

@Component({ standalone: true, imports: [FormsModule, RouterLink, MapComponent], styleUrl: '../public.css', templateUrl: './public.component.html' })
export class PublicComponent implements AfterViewInit, OnDestroy {
  private readonly api = inject(BusinessDirectoryApiService);
  private readonly router = inject(Router);
  readonly categories = BUSINESS_CATEGORIES;
  readonly categoryLabel = businessCategoryLabel;
  activeCategories = new Set<string>();
  name = '';
  visibleCount = 10;
  businesses: DirectoryBusiness[] = [];
  visitorPosition: MapPosition | null = null;
  mapBusinesses: DirectoryBusiness[] = [];
  mapMarkers: MapPosition[] = [];
  @ViewChild('loadMoreSentinel') private loadMoreSentinel?: ElementRef<HTMLDivElement>;
  private observer?: IntersectionObserver;
  private loadingMore = false;
  private searchTimeout?: ReturnType<typeof setTimeout>;
  private searchVersion = 0;

  constructor() {
    if (typeof navigator === 'undefined' || !navigator.geolocation) { this.search(); return; }
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => { this.visitorPosition = { lat: coords.latitude, lng: coords.longitude }; this.search(); },
      () => this.search(),
    );
  }

  search(): void {
    const position = this.visitorPosition;
    const version = ++this.searchVersion;
    this.api.get(undefined, undefined, this.name.trim() || undefined, position?.lat, position?.lng).subscribe({
      next: businesses => {
        if (version !== this.searchVersion) return;
        this.businesses = businesses;
        this.visibleCount = 10;
        this.updateMap();
      },
      error: () => {
        if (version !== this.searchVersion) return;
        this.businesses = []; this.visibleCount = 10; this.mapBusinesses = []; this.mapMarkers = [];
      },
    });
  }

  get visibleBusinesses(): DirectoryBusiness[] {
    return this.filteredBusinesses.slice(0, this.visibleCount);
  }

  toggleCategory(value: string): void {
    this.activeCategories.has(value) ? this.activeCategories.delete(value) : this.activeCategories.add(value);
    this.visibleCount = 10;
    this.updateMap();
  }

  ngAfterViewInit(): void {
    if (typeof IntersectionObserver === 'undefined' || !this.loadMoreSentinel) return;
    this.observer = new IntersectionObserver(entries => {
      if (entries.some(entry => entry.isIntersecting)) this.loadMore();
    });
    this.observer.observe(this.loadMoreSentinel.nativeElement);
  }

  ngOnDestroy(): void {
    this.observer?.disconnect();
    if (this.searchTimeout !== undefined) clearTimeout(this.searchTimeout);
  }

  scheduleSearch(): void {
    if (this.searchTimeout !== undefined) clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => {
      this.searchTimeout = undefined;
      this.search();
    }, 300);
  }

  loadMore(): void {
    const total = this.filteredBusinesses.length;
    if (this.loadingMore || this.visibleCount >= total) return;
    this.loadingMore = true;
    this.visibleCount = Math.min(this.visibleCount + 10, total);
    queueMicrotask(() => { this.loadingMore = false; });
  }

  private get filteredBusinesses(): DirectoryBusiness[] {
    return this.activeCategories.size
      ? this.businesses.filter(business => business.category != null && this.activeCategories.has(business.category))
      : this.businesses;
  }

  private updateMap(): void {
    this.mapBusinesses = this.filteredBusinesses.filter(business => business.latitude != null && business.longitude != null);
    this.mapMarkers = this.mapBusinesses.map(business => ({ lat: business.latitude!, lng: business.longitude!, label: business.name }));
  }

  openBusiness(index: number): void { this.router.navigate(['/public/catalog', this.mapBusinesses[index].slug]); }
}
