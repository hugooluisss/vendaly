import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { BusinessApiService } from '../business-api.service';
import { CategoryApiService } from '../category-api.service';
import { ProductApiService } from '../product-api.service';
import { Business } from '../business.models';

@Component({ standalone: true, imports: [RouterLink], styleUrl: './dashboard-home.component.css', templateUrl: './dashboard-home.component.html' })
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
