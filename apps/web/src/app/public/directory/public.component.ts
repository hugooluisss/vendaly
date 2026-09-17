import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BusinessDirectoryApiService } from '../business-directory-api.service';
import { DirectoryBusiness } from '../public.models';
import { BUSINESS_CATEGORIES, businessCategoryLabel } from '../../shared/business-category';
import { MapComponent, MapPosition } from '../../shared/map/map.component';
import { ModalComponent } from '../../shared/modal/modal.component';

export interface DirectoryBusinessGroup {
  label: string;
  businesses: DirectoryBusiness[];
}

export function groupDirectoryBusinesses(businesses: DirectoryBusiness[]): DirectoryBusinessGroup[] {
  const supportedCategories = new Set<string>(BUSINESS_CATEGORIES.map(category => category.value));
  const groups = BUSINESS_CATEGORIES
    .map(category => ({ label: category.label, businesses: businesses.filter(business => business.category === category.value) }))
    .filter(group => group.businesses.length);
  const uncategorized = businesses.filter(business => !business.category || !supportedCategories.has(business.category));
  return uncategorized.length ? [...groups, { label: 'Otros', businesses: uncategorized }] : groups;
}

@Component({ standalone: true, imports: [FormsModule, RouterLink, MapComponent, ModalComponent], styleUrl: '../public.css', templateUrl: './public.component.html' })
export class PublicComponent {
  private readonly api = inject(BusinessDirectoryApiService);
  private readonly router = inject(Router);
  readonly categories = BUSINESS_CATEGORIES;
  readonly categoryLabel = businessCategoryLabel;
  category = '';
  name = '';
  draftCategory = '';
  draftName = '';
  filterModalOpen = false;
  businesses: DirectoryBusiness[] = [];
  categoryGroups: DirectoryBusinessGroup[] = [];
  visitorPosition: MapPosition | null = null;
  mapBusinesses: DirectoryBusiness[] = [];
  mapMarkers: MapPosition[] = [];

  constructor() {
    if (typeof navigator === 'undefined' || !navigator.geolocation) { this.search(); return; }
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => { this.visitorPosition = { lat: coords.latitude, lng: coords.longitude }; this.search(); },
      () => this.search(),
    );
  }

  search(): void {
    const position = this.visitorPosition;
    this.api.get(this.category || undefined, undefined, this.name.trim() || undefined, position?.lat, position?.lng).subscribe({
      next: businesses => {
        this.businesses = businesses;
        this.categoryGroups = groupDirectoryBusinesses(businesses);
        this.mapBusinesses = businesses.filter(business => business.latitude != null && business.longitude != null);
        this.mapMarkers = this.mapBusinesses.map(business => ({ lat: business.latitude!, lng: business.longitude!, label: business.name }));
      },
      error: () => { this.businesses = []; this.categoryGroups = []; this.mapBusinesses = []; this.mapMarkers = []; },
    });
  }

  openFilterModal(): void {
    this.draftCategory = this.category;
    this.draftName = this.name;
    this.filterModalOpen = true;
  }

  closeFilterModal(): void { this.filterModalOpen = false; }

  submitFilters(): void {
    this.category = this.draftCategory;
    this.name = this.draftName;
    this.search();
    this.closeFilterModal();
  }

  openBusiness(index: number): void { this.router.navigate(['/public/catalog', this.mapBusinesses[index].slug]); }
}
