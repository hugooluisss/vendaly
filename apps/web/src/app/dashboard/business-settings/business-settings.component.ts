import { Component, ViewChild, inject } from '@angular/core';
import { Location } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { FormsModule, FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { BusinessApiService } from '../business-api.service';
import { ModalComponent } from '../../shared/modal/modal.component';
import { ImageUploadComponent } from '../../shared/image-upload/image-upload.component';
import { BUSINESS_CATEGORIES } from '../../shared/business-category';
import { MapComponent, MapPosition } from '../../shared/map/map.component';
import { QrCodeComponent } from '../../shared/qr-code/qr-code.component';
import { NotificationService } from '../../shared/notification.service';
import { FulfillmentMethod, OrderStatus, PaymentMethod } from '../business.models';
import { PaymentMethodsModalComponent } from './payment-methods-modal/payment-methods-modal.component';
import { OrderStatusesModalComponent } from './order-statuses-modal/order-statuses-modal.component';
import { FulfillmentMethodsModalComponent } from './fulfillment-methods-modal/fulfillment-methods-modal.component';

const DAYS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
const DEFAULT_POSITION: MapPosition = { lat: 19.4326, lng: -99.1332 };
export function unconfiguredDays(hours: { day_of_week: number }[]): number[] { const configured = new Set(hours.map(hour => hour.day_of_week)); return DAYS.map((_, day) => day).filter(day => !configured.has(day)); }

@Component({ standalone: true, imports: [FormsModule, ReactiveFormsModule, RouterLink, ModalComponent, ImageUploadComponent, MapComponent, QrCodeComponent, FulfillmentMethodsModalComponent, PaymentMethodsModalComponent, OrderStatusesModalComponent], styleUrl: './business-settings.component.css', templateUrl: './business-settings.component.html' })
export class BusinessSettingsComponent {
  private readonly api = inject(BusinessApiService); private readonly location = inject(Location); private readonly router = inject(Router); readonly notification = inject(NotificationService); @ViewChild(QrCodeComponent) qrCode?: QrCodeComponent; businessId = 0; publicCatalogUrl?: string; logo?: File; coverImage?: File; coverImageUrl?: string | null; mapCenter = DEFAULT_POSITION; position?: MapPosition; hoursModal = false; fulfillmentModalOpen = false; paymentMethodsModalOpen = false; orderStatusesModalOpen = false; initialFulfillmentMethods: FulfillmentMethod[] = []; paymentMethods: PaymentMethod[] = []; orderStatuses: OrderStatus[] = []; hours = Array.from({ length: 7 }, (_, day) => ({ day_of_week: day, opens_at: day === 0 || day === 6 ? null : '09:00', closes_at: day === 0 || day === 6 ? null : '20:00', is_closed: day === 0 || day === 6 })); readonly dayNames = DAYS; readonly categories = BUSINESS_CATEGORIES;
  readonly profile = new FormGroup({ name: new FormControl('', { nonNullable: true, validators: [Validators.required] }), whatsapp_number: new FormControl('', { nonNullable: true }), facebook_url: new FormControl('', { nonNullable: true }), instagram_url: new FormControl('', { nonNullable: true }), website_url: new FormControl('', { nonNullable: true }), description: new FormControl('', { nonNullable: true }), category: new FormControl<string | null>(null), location: new FormControl('', { nonNullable: true }), wallet_enabled: new FormControl(false, { nonNullable: true }) });
  get publicCatalogQrUrl(): string | undefined { return this.publicCatalogUrl ? `${this.publicCatalogUrl}?src=qr` : undefined; }
  constructor() { this.api.getMine().subscribe({ next: result => { this.businessId = result.business.id; this.publicCatalogUrl = this.businessId && result.business.slug ? window.location.origin + this.location.prepareExternalUrl(this.router.serializeUrl(this.router.createUrlTree(['/public/catalog', result.business.slug]))) : undefined; this.coverImageUrl = result.business.cover_image_url; this.initialFulfillmentMethods = [...(result.business.fulfillment_methods ?? [])].sort((a, b) => a.position - b.position); this.paymentMethods = [...(result.business.payment_methods ?? [])].sort((a, b) => a.position - b.position); this.orderStatuses = [...(result.business.order_statuses ?? [])].sort((a, b) => a.position - b.position); if (result.business.latitude != null && result.business.longitude != null) { this.position = { lat: result.business.latitude, lng: result.business.longitude }; this.mapCenter = this.position; } this.profile.patchValue({ name: result.business.name, whatsapp_number: result.business.whatsapp_number ?? '', facebook_url: result.business.facebook_url ?? '', instagram_url: result.business.instagram_url ?? '', website_url: result.business.website_url ?? '', description: result.business.description ?? '', category: result.business.category ?? null, location: result.business.location ?? '', wallet_enabled: result.business.wallet_enabled ?? false }); for (const saved of result.hours) Object.assign(this.hours[saved.day_of_week], saved); }, error: () => { this.businessId = 0; this.publicCatalogUrl = undefined; } }); }
  downloadQr(): void { this.qrCode?.download('catalogo-qr.png'); }
  saveProfile(): void { if (this.profile.invalid || !this.businessId) return; const fields = { ...this.profile.getRawValue(), latitude: this.position?.lat ?? null, longitude: this.position?.lng ?? null }; this.api.update(this.businessId, fields, this.logo, this.coverImage).subscribe({ next: () => this.notification.success('Perfil guardado con éxito.'), error: response => this.notification.error(response.error?.error ?? 'No se pudo guardar el perfil.') }); }
  onLogoSelected(file: File): void { this.logo = file; }
  onCoverSelected(file: File): void { this.coverImage = file; }
  onPositionChanged(position: MapPosition): void { this.position = position; this.mapCenter = position; }
  saveHours(): void { if (this.businessId) this.api.saveHours(this.businessId, this.hours).subscribe(() => this.hoursModal = false); }
}
