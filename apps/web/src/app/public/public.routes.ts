import { Routes } from '@angular/router';

export const PUBLIC_ROUTES: Routes = [
  { path: 'catalog/:slug/order', loadComponent: () => import('./order-summary.component').then(m => m.OrderSummaryComponent) },
  { path: 'catalog/:slug', loadComponent: () => import('./catalog-page.component').then(m => m.CatalogPageComponent) },
  { path: '', loadComponent: () => import('./public.component').then(m => m.PublicComponent) },
];
