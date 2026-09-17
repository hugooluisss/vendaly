import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { BusinessApiService } from '../business-api.service';
import { OrderApiService } from '../order-api.service';
import { Order } from '../order.models';

type Range = 'today' | '7' | '30' | 'custom';

@Component({
  standalone: true,
  imports: [CurrencyPipe, DatePipe, FormsModule],
  templateUrl: './order-history.component.html',
  styleUrl: './order-history.component.css'
})
export class OrderHistoryComponent {
  private readonly businessApi = inject(BusinessApiService);
  private readonly ordersApi = inject(OrderApiService);
  businessId = 0; range: Range = 'today'; from = this.today(); to = this.from;
  orders: Order[] = []; count = 0; loading = false; error = '';

  constructor() { this.businessApi.getMine().subscribe({ next: ({ business }) => { this.businessId = business.id; this.load(); }, error: () => this.error = 'No se pudo cargar el negocio.' }); }

  selectRange(range: Range): void { this.range = range; if (range !== 'custom') { const days = Number(range === 'today' ? 1 : range); this.to = this.today(); this.from = this.dateOffset(this.to, -(days - 1)); this.load(); } }
  load(): void {
    if (!this.businessId || !this.from || !this.to) return;
    this.loading = true; this.error = '';
    this.ordersApi.list(this.businessId, this.from, this.to).subscribe({ next: result => { this.orders = result.orders; this.count = result.count; this.loading = false; }, error: () => { this.error = 'No se pudieron cargar los pedidos.'; this.loading = false; } });
  }
  applyCustomRange(): void { this.load(); }
  private today(): string { return this.formatDate(new Date()); }
  private dateOffset(date: string, offset: number): string { const value = new Date(`${date}T12:00:00`); value.setDate(value.getDate() + offset); return this.formatDate(value); }
  private formatDate(date: Date): string { return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-'); }
}
