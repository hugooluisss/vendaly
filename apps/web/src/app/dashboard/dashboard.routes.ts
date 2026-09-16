import { Routes } from '@angular/router';
import { authGuard } from '../auth/auth.guard';
import { DashboardComponent } from './dashboard.component';

export const DASHBOARD_ROUTES: Routes = [
  { path: 'auth/login', data: { mode: 'login' }, loadComponent: () => import('./auth/auth.component').then(m => m.AuthComponent) },
  { path: 'auth/register', data: { mode: 'register' }, loadComponent: () => import('./auth/auth.component').then(m => m.AuthComponent) },
  { path: '', canActivate: [authGuard], component: DashboardComponent, children: [
    { path: '', loadComponent: () => import('./dashboard-home.component').then(m => m.DashboardHomeComponent) },
    { path: 'onboarding', loadComponent: () => import('./onboarding.component').then(m => m.OnboardingComponent) },
    { path: 'settings', loadComponent: () => import('./business-settings.component').then(m => m.BusinessSettingsComponent) },
    { path: 'catalog', loadComponent: () => import('./catalog-management.component').then(m => m.CatalogManagementComponent) },
  ] }
];
