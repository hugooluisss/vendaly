import { CurrencyPipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { CartService } from '../cart.service';
@Component({ standalone: true, imports: [CurrencyPipe, FormsModule], templateUrl: './order-summary.component.html', styleUrl: '../public.css' })
export class OrderSummaryComponent {
  private readonly route = inject(ActivatedRoute); private readonly router = inject(Router); readonly cart = inject(CartService); readonly slug = this.route.snapshot.paramMap.get('slug') ?? '';
  back(): void { this.router.navigate(['/public/catalog', this.slug]); }
  continue(): void { if (this.cart.items.length) this.router.navigate(['/public/catalog', this.slug, 'order', 'checkout']); }
}
