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
import { OrderApiService, PublicOrderResponse } from '../order-api.service';
import { PublicFulfillmentMethod } from '../public.models';

const DEFAULT_POSITION: MapPosition = { lat: 19.4326, lng: -99.1332 };

@Component({ standalone: true, imports: [AsyncPipe, CurrencyPipe, FormsModule, MapComponent], templateUrl: './order-checkout.component.html', styleUrl: '../public.css' })
export class OrderCheckoutComponent {
  private readonly route = inject(ActivatedRoute); private readonly router = inject(Router); private readonly orders = inject(OrderApiService); private readonly locations = inject(DeliveryLocationService); private readonly phones = inject(CustomerPhoneService); private readonly catalogApi = inject(CatalogApiService);
  readonly cart = inject(CartService); readonly slug = this.route.snapshot.paramMap.get('slug') ?? ''; readonly mapCenter = DEFAULT_POSITION;
  readonly catalog$ = this.catalogApi.get(this.slug).pipe(catchError(() => of(null)), shareReplay(1));
  selectedMethod: number | null = null; selectedPaymentMethod: number | null = null; customerPhone = ''; walletEnabled = false; knownWalletBalance: number | null = null; walletRedemption: number | null = null; orderResponse: PublicOrderResponse | null = null; deliveryAddress = ''; deliveryPosition?: MapPosition; submitting = false; error = ''; fulfillmentMethods: PublicFulfillmentMethod[] = [];
  constructor() { const saved = this.locations.load(); this.deliveryAddress = saved.address; this.deliveryPosition = saved.position; this.customerPhone = this.phones.load(); this.catalog$.subscribe(catalog => { this.fulfillmentMethods = catalog?.fulfillment_methods ?? []; this.walletEnabled = !!catalog?.business.wallet_enabled; this.loadKnownWalletBalance(); }); }
  private walletCacheKey(): string { return `vendaly.wallet.${this.slug}.${this.customerPhone.replace(/\D+/g, '')}`; }
  loadKnownWalletBalance(): void { this.knownWalletBalance = null; if (!this.walletEnabled || !this.customerPhone.replace(/\D+/g, '')) return; try { const value = sessionStorage.getItem(this.walletCacheKey()); this.knownWalletBalance = value == null ? null : Number(value); } catch { this.knownWalletBalance = null; } }
  phoneChanged(): void { this.walletRedemption = null; this.loadKnownWalletBalance(); }
  get maxWalletRedemption(): number { return this.knownWalletBalance == null ? 0 : Math.min(this.knownWalletBalance, this.grandTotal()); }
  get appliedWalletRedemption(): number { const requested = Number(this.walletRedemption) || 0; return Math.max(0, Math.min(requested, this.maxWalletRedemption)); }
  get payableTotal(): number { return this.grandTotal() - this.appliedWalletRedemption; }
  methodFee(method: PublicFulfillmentMethod): number { return Number(method.fee) || 0; }
  get selectedMethodRequiresAddress(): boolean { return this.fulfillmentMethods.find(method => method.id === this.selectedMethod)?.requires_address ?? false; }
  selectedFee(methods: PublicFulfillmentMethod[] = this.fulfillmentMethods): number { const method = methods.find(item => item.id === this.selectedMethod); return method ? this.methodFee(method) : 0; }
  grandTotal(methods: PublicFulfillmentMethod[] = this.fulfillmentMethods): number { return this.cart.total() + this.selectedFee(methods); }
  canSubmit(): boolean { return !!this.customerPhone.trim() && this.selectedMethod != null && this.selectedPaymentMethod != null && (!this.selectedMethodRequiresAddress || !!this.deliveryAddress.trim() || !!this.deliveryPosition); }
  onDeliveryPositionChanged(position: MapPosition): void { this.deliveryPosition = position; }
  submit(): void {
    if (!this.cart.items.length || this.submitting || !this.canSubmit()) { this.error = 'Ingresa tu teléfono, selecciona un método de entrega y pago, y completa los datos de entrega.'; return; }
    this.submitting = true; this.error = ''; const location: DeliveryLocation = { address: this.deliveryAddress, position: this.deliveryPosition }; const phone = this.customerPhone;
    this.orders.create(this.slug, this.cart.items, this.cart.total(), this.selectedMethod!, location, this.selectedPaymentMethod, phone, this.walletEnabled ? this.appliedWalletRedemption : null).subscribe({ next: response => { if (this.selectedMethodRequiresAddress) this.locations.persist(location); this.phones.persist(phone); this.orderResponse = response; if (response.wallet_enabled && response.wallet_balance != null) { try { sessionStorage.setItem(this.walletCacheKey(), response.wallet_balance); } catch {} this.knownWalletBalance = Number(response.wallet_balance); } this.submitting = false; }, error: () => { this.error = 'No se pudo enviar la selección. Intenta de nuevo.'; this.submitting = false; } });
  }
  openWhatsApp(): void { if (this.orderResponse?.whatsapp_url) window.open(this.orderResponse.whatsapp_url, '_blank', 'noopener'); }
  back(): void { this.router.navigate(['/public/catalog', this.slug, 'order']); }
}
