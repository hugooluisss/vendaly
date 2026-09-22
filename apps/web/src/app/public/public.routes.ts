import { Routes } from '@angular/router';

export const PUBLIC_ROUTES: Routes = [
  { path: 'catalog/:slug/order/checkout', loadComponent: () => import('./order-checkout/order-checkout.component').then(m => m.OrderCheckoutComponent) },
  { path: 'catalog/:slug/order', loadComponent: () => import('./order-summary/order-summary.component').then(m => m.OrderSummaryComponent) },
  { path: 'catalog/:slug', loadComponent: () => import('./catalog-page/catalog-page.component').then(m => m.CatalogPageComponent) },
  { path: '', loadComponent: () => import('./directory/public.component').then(m => m.PublicComponent) },
];
