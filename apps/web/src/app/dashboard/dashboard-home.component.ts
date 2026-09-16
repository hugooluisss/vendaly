import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { BusinessApiService } from './business-api.service';
import { CategoryApiService } from './category-api.service';
import { ProductApiService } from './product-api.service';
import { Business } from './business.models';

@Component({ standalone: true, imports: [RouterLink], styleUrl: './dashboard-home.component.css', template: `<section class="card hero"><p class="eyebrow">PANEL DE ADMINISTRACIÓN</p><h1>{{ business ? business.name : 'Tu negocio' }}</h1><p>{{ business ? 'Aquí tienes un resumen de tu negocio y catálogo.' : 'Configura tu negocio y después arma tu catálogo.' }}</p>@if (!business) {<a class="button" routerLink="/dashboard/onboarding">Crear o configurar negocio</a>}</section>@if (business) {<section class="stats" aria-label="Resumen del negocio"><article class="stat"><strong class="stat-name">{{ business.name }}</strong><span>Negocio</span><small [class.published]="business.is_published">{{ business.is_published ? 'Publicado' : 'Sin publicar' }}</small></article><article class="stat"><strong>{{ categoryCount }}</strong><span>Categorías</span></article><article class="stat"><strong>{{ productCount }}</strong><span>Productos</span></article><article class="stat"><strong>{{ activeProducts }}</strong><span>Disponibles</span><small>{{ inactiveProducts }} pausado{{ inactiveProducts === 1 ? '' : 's' }}</small></article></section>}<section class="card"><h2>Accesos rápidos</h2><div class="links"><a routerLink="/dashboard/settings">Perfil y horarios</a><a routerLink="/dashboard/catalog">Categorías y productos</a></div></section>` })
export class DashboardHomeComponent {
  private readonly businessApi = inject(BusinessApiService);
  private readonly categoriesApi = inject(CategoryApiService);
  private readonly productsApi = inject(ProductApiService);
  business?: Business;
  categoryCount = 0; productCount = 0; activeProducts = 0; inactiveProducts = 0;

  constructor() {
    this.businessApi.getMine().subscribe({ next: ({ business }) => {
      this.business = business;
      this.categoriesApi.list(business.id).subscribe(items => this.categoryCount = items.length);
      this.productsApi.list(business.id).subscribe(items => {
        this.productCount = items.length;
        this.activeProducts = items.filter(item => item.is_active).length;
        this.inactiveProducts = items.length - this.activeProducts;
      });
    }, error: () => this.business = undefined });
  }
}
