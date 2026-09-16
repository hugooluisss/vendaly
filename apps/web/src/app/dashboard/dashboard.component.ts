import { Component, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../auth/auth.service';
import { BusinessApiService } from './business-api.service';

@Component({
  standalone: true,
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.css'
})
export class DashboardComponent {
  private readonly auth = inject(AuthService);
  private readonly businessApi = inject(BusinessApiService);
  businessName = 'Tu negocio';

  constructor() {
    this.businessApi.getMine().subscribe({ next: result => this.businessName = result.business.name, error: () => this.businessName = 'Tu negocio' });
  }

  logout(): void {
    this.auth.logout();
  }
}
