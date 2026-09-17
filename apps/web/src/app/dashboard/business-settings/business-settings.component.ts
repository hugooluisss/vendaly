import { Component, inject } from '@angular/core';
import { FormsModule, FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { BusinessApiService } from '../business-api.service';
import { ModalComponent } from '../../shared/modal/modal.component';
import { ImageUploadComponent } from '../../shared/image-upload/image-upload.component';
import { BUSINESS_CATEGORIES } from '../../shared/business-category';
import { MapComponent, MapPosition } from '../../shared/map/map.component';

const DAYS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
const DEFAULT_POSITION: MapPosition = { lat: 19.4326, lng: -99.1332 };
export function unconfiguredDays(hours: { day_of_week: number }[]): number[] { const configured = new Set(hours.map(hour => hour.day_of_week)); return DAYS.map((_, day) => day).filter(day => !configured.has(day)); }

@Component({ standalone: true, imports: [FormsModule, ReactiveFormsModule, ModalComponent, ImageUploadComponent, MapComponent], styleUrl: './business-settings.component.css', templateUrl: './business-settings.component.html' })
export class BusinessSettingsComponent {
  private readonly api = inject(BusinessApiService); businessId = 0; logo?: File; coverImage?: File; coverImageUrl?: string | null; mapCenter = DEFAULT_POSITION; position?: MapPosition; hoursModal = false; hours = Array.from({ length: 7 }, (_, day) => ({ day_of_week: day, opens_at: '09:00', closes_at: '18:00', is_closed: false })); readonly dayNames = DAYS; readonly categories = BUSINESS_CATEGORIES;
  readonly profile = new FormGroup({ name: new FormControl('', { nonNullable: true, validators: [Validators.required] }), whatsapp_number: new FormControl('', { nonNullable: true }), description: new FormControl('', { nonNullable: true }), category: new FormControl<string | null>(null), location: new FormControl('', { nonNullable: true }) });
  constructor() { this.api.getMine().subscribe({ next: result => { this.businessId = result.business.id; this.coverImageUrl = result.business.cover_image_url; if (result.business.latitude != null && result.business.longitude != null) { this.position = { lat: result.business.latitude, lng: result.business.longitude }; this.mapCenter = this.position; } this.profile.patchValue({ name: result.business.name, whatsapp_number: result.business.whatsapp_number ?? '', description: result.business.description ?? '', category: result.business.category ?? null, location: result.business.location ?? '' }); for (const saved of result.hours) Object.assign(this.hours[saved.day_of_week], saved); }, error: () => this.businessId = 0 }); }
  saveProfile(): void { if (this.profile.invalid || !this.businessId) return; this.api.update(this.businessId, { ...this.profile.getRawValue(), latitude: this.position?.lat ?? null, longitude: this.position?.lng ?? null }, this.logo, this.coverImage).subscribe(); }
  onLogoSelected(file: File): void { this.logo = file; }
  onCoverSelected(file: File): void { this.coverImage = file; }
  onPositionChanged(position: MapPosition): void { this.position = position; this.mapCenter = position; }
  saveHours(): void { if (this.businessId) this.api.saveHours(this.businessId, this.hours).subscribe(() => this.hoursModal = false); }
}
