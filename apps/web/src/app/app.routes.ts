import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: 'dashboard', loadChildren: () => import('./dashboard/dashboard.routes').then(m => m.DASHBOARD_ROUTES) },
  { path: 'public', loadChildren: () => import('./public/public.routes').then(m => m.PUBLIC_ROUTES) },
  { path: '', pathMatch: 'full', redirectTo: 'public' },
];
