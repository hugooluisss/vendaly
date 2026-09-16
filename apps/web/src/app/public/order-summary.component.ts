import { CurrencyPipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { CartService } from './cart.service';
import { OrderApiService } from './order-api.service';

@Component({ standalone: true, imports: [CurrencyPipe, FormsModule], templateUrl: './order-summary.component.html', styleUrl: './public.css' })
export class OrderSummaryComponent {
  private readonly route = inject(ActivatedRoute); private readonly router = inject(Router); private readonly orders = inject(OrderApiService);
  readonly cart = inject(CartService); readonly slug = this.route.snapshot.paramMap.get('slug') ?? '';
  submitting = false; error = '';
  submit(): void {
    if (!this.cart.items.length || this.submitting) return;
    this.submitting = true; this.error = '';
    this.orders.create(this.slug, this.cart.items, this.cart.total()).subscribe({ next: response => window.location.assign(response.whatsapp_url), error: () => { this.error = 'No se pudo enviar la selección. Intenta de nuevo.'; this.submitting = false; } });
  }
  back(): void { this.router.navigate(['/public/catalog', this.slug]); }
}
