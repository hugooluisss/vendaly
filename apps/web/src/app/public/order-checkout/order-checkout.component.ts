import { AsyncPipe, CurrencyPipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { catchError, of, shareReplay } from 'rxjs';
import { MapComponent, MapPosition } from '../../shared/map/map.component';
import { CartService } from '../cart.service';
import { CatalogApiService } from '../catalog-api.service';
import { CustomerPhoneService } from '../customer-phone.service';
import { DeliveryLocation, DeliveryLocationService } from '../delivery-location.service';
import { OrderApiService } from '../order-api.service';
import { PublicFulfillmentMethod } from '../public.models';

const DEFAULT_POSITION: MapPosition = { lat: 19.4326, lng: -99.1332 };

@Component({ standalone: true, imports: [AsyncPipe, CurrencyPipe, FormsModule, MapComponent], templateUrl: './order-checkout.component.html', styleUrl: '../public.css' })
export class OrderCheckoutComponent {
  private readonly route = inject(ActivatedRoute); private readonly router = inject(Router); private readonly orders = inject(OrderApiService); private readonly locations = inject(DeliveryLocationService); private readonly phones = inject(CustomerPhoneService); private readonly catalogApi = inject(CatalogApiService);
  readonly cart = inject(CartService); readonly slug = this.route.snapshot.paramMap.get('slug') ?? ''; readonly mapCenter = DEFAULT_POSITION;
  readonly catalog$ = this.catalogApi.get(this.slug).pipe(catchError(() => of(null)), shareReplay(1));
  selectedMethod = ''; selectedPaymentMethod: number | null = null; customerPhone = ''; deliveryAddress = ''; deliveryPosition?: MapPosition; submitting = false; error = '';
  constructor() { const saved = this.locations.load(); this.deliveryAddress = saved.address; this.deliveryPosition = saved.position; this.customerPhone = this.phones.load(); }
  methodType(method: PublicFulfillmentMethod | string): string { return typeof method === 'string' ? method : method.type; }
  methodFee(method: PublicFulfillmentMethod | string): number { return typeof method === 'string' ? 0 : Number(method.fee) || 0; }
  selectedFee(methods: PublicFulfillmentMethod[] = []): number { const method = methods.find(item => this.methodType(item) === this.selectedMethod); return method ? this.methodFee(method) : 0; }
  grandTotal(methods: PublicFulfillmentMethod[] = []): number { return this.cart.total() + this.selectedFee(methods); }
  canSubmit(): boolean { return !!this.customerPhone.trim() && !!this.selectedMethod && this.selectedPaymentMethod != null && (this.selectedMethod !== 'delivery' || !!this.deliveryAddress.trim() || !!this.deliveryPosition); }
  onDeliveryPositionChanged(position: MapPosition): void { this.deliveryPosition = position; }
  submit(): void {
    if (!this.cart.items.length || this.submitting || !this.canSubmit()) { this.error = 'Ingresa tu teléfono, selecciona un método de entrega y pago, y completa los datos de entrega.'; return; }
    this.submitting = true; this.error = ''; const location: DeliveryLocation = { address: this.deliveryAddress, position: this.deliveryPosition }; const phone = this.customerPhone;
    this.orders.create(this.slug, this.cart.items, this.cart.total(), this.selectedMethod, location, this.selectedPaymentMethod, phone).subscribe({ next: response => { if (this.selectedMethod === 'delivery') this.locations.persist(location); this.phones.persist(phone); window.open(response.whatsapp_url, '_blank', 'noopener'); this.submitting = false; }, error: () => { this.error = 'No se pudo enviar la selección. Intenta de nuevo.'; this.submitting = false; } });
  }
  back(): void { this.router.navigate(['/public/catalog', this.slug, 'order']); }
}
