import { Component, EventEmitter, Input, OnInit, Output, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, ArrowUp, ArrowDown, Pencil, Trash2 } from 'lucide-angular';
import { forkJoin } from 'rxjs';
import { BusinessApiService } from '../../business-api.service';
import { OrderStatus } from '../../business.models';
import { ModalComponent } from '../../../shared/modal/modal.component';
import { NotificationService } from '../../../shared/notification.service';

@Component({ selector: 'app-order-statuses-modal', standalone: true, imports: [FormsModule, ModalComponent, LucideAngularModule], styleUrl: './order-statuses-modal.component.css', templateUrl: './order-statuses-modal.component.html' })
export class OrderStatusesModalComponent implements OnInit {
  readonly icons = { ArrowUp, ArrowDown, Pencil, Trash2 };
  private readonly api = inject(BusinessApiService);
  readonly notification = inject(NotificationService);
  @Input({ required: true }) businessId!: number;
  @Input() initialStatuses: OrderStatus[] = [];
  @Output() close = new EventEmitter<void>();

  orderStatuses: OrderStatus[] = [];
  editingStatus: OrderStatus | null = null;
  orderStatusInput = '';
  orderStatusColor = '#EA580C';
  orderStatusTerminal = false;
  orderStatusReversesWallet = false;

  ngOnInit(): void {
    this.orderStatuses = [...this.initialStatuses].sort((a, b) => a.position - b.position);
  }

  startEdit(status: OrderStatus): void {
    this.editingStatus = status;
    this.orderStatusInput = status.name;
    this.orderStatusColor = status.color;
    this.orderStatusTerminal = status.is_terminal;
    this.orderStatusReversesWallet = !!status.reverses_wallet;
  }

  cancelEdit(): void {
    this.editingStatus = null;
    this.orderStatusInput = '';
    this.orderStatusColor = '#EA580C';
    this.orderStatusTerminal = false;
    this.orderStatusReversesWallet = false;
  }

  submit(): void {
    if (this.editingStatus) this.saveEdit(this.editingStatus);
    else this.addOrderStatus();
  }

  addOrderStatus(): void {
    const name = this.orderStatusInput.trim();
    if (!name || !this.businessId) return;
    this.api.addOrderStatus(this.businessId, name, this.orderStatusColor, this.orderStatusTerminal).subscribe({
      next: status => {
        this.orderStatuses = [...this.orderStatuses, status];
        if (this.orderStatusReversesWallet) this.updateNewStatusWallet(status);
        else this.cancelEdit();
      },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo agregar el estado del pedido.')
    });
  }

  setDefaultOrderStatus(status: OrderStatus): void {
    if (!this.businessId || status.is_default) return;
    this.api.setOrderStatusDefault(this.businessId, status.id).subscribe({
      next: saved => {
        this.orderStatuses.forEach(item => item.is_default = item.id === status.id);
        Object.assign(status, saved, { is_default: true });
      },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo establecer el estado predeterminado.')
    });
  }

  deleteOrderStatus(status: OrderStatus): void {
    if (this.orderStatuses.length === 1 || status.is_default || !this.businessId) return;
    this.api.deleteOrderStatus(this.businessId, status.id).subscribe({
      next: () => {
        this.orderStatuses = this.orderStatuses.filter(item => item.id !== status.id);
        if (this.editingStatus?.id === status.id) this.cancelEdit();
      },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo eliminar el estado del pedido.')
    });
  }

  moveOrderStatus(status: OrderStatus, direction: -1 | 1): void {
    const index = this.orderStatuses.indexOf(status);
    const next = index + direction;
    if (next < 0 || next >= this.orderStatuses.length || !this.businessId) return;
    [this.orderStatuses[index], this.orderStatuses[next]] = [this.orderStatuses[next], this.orderStatuses[index]];
    this.api.reorderOrderStatuses(this.businessId, this.orderStatuses.map(item => item.id)).subscribe({
      error: response => this.notification.error(response.error?.error ?? 'No se pudo reordenar los estados del pedido.')
    });
  }

  private saveEdit(status: OrderStatus): void {
    const name = this.orderStatusInput.trim();
    if (!name || !this.businessId) return;
    forkJoin({
      renamed: this.api.renameOrderStatus(this.businessId, status.id, name),
      recolored: this.api.recolorOrderStatus(this.businessId, status.id, this.orderStatusColor),
      terminal: this.api.setOrderStatusTerminal(this.businessId, status.id, this.orderStatusTerminal),
      wallet: this.api.setOrderStatusReversesWallet(this.businessId, status.id, this.orderStatusReversesWallet)
    }).subscribe({
      next: ({ renamed, recolored, terminal, wallet }) => {
        Object.assign(status, renamed, recolored, terminal, wallet);
        this.cancelEdit();
      },
      error: () => this.notification.error('No se pudo actualizar el estado del pedido.')
    });
  }

  private updateNewStatusWallet(status: OrderStatus): void {
    this.api.setOrderStatusReversesWallet(this.businessId, status.id, true).subscribe({
      next: saved => { Object.assign(status, saved); this.cancelEdit(); },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo actualizar la reversión del monedero.')
    });
  }
}
