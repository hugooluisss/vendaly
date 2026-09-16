import { AsyncPipe, CurrencyPipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { catchError, of, shareReplay } from 'rxjs';
import { CartService } from './cart.service';
import { CatalogApiService } from './catalog-api.service';

@Component({ standalone: true, imports: [AsyncPipe, CurrencyPipe], templateUrl: './catalog-page.component.html', styleUrl: './public.css' })
export class CatalogPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly catalogApi = inject(CatalogApiService);
  readonly cart = inject(CartService);
  readonly slug = this.route.snapshot.paramMap.get('slug') ?? '';
  readonly catalog$ = this.catalogApi.get(this.slug).pipe(catchError(() => of(null)), shareReplay(1));
  openOrder(): void { this.router.navigate(['/public/catalog', this.slug, 'order']); }
}
