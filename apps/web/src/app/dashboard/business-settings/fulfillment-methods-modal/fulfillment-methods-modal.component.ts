import { Component, EventEmitter, Input, OnInit, Output, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Pencil, Trash2 } from 'lucide-angular';
import { BusinessApiService } from '../../business-api.service';
import { FulfillmentMethod } from '../../business.models';
import { ModalComponent } from '../../../shared/modal/modal.component';
import { NotificationService } from '../../../shared/notification.service';

@Component({ selector: 'app-fulfillment-methods-modal', standalone: true, imports: [FormsModule, ModalComponent, LucideAngularModule], styleUrl: './fulfillment-methods-modal.component.css', templateUrl: './fulfillment-methods-modal.component.html' })
export class FulfillmentMethodsModalComponent implements OnInit {
  readonly icons = { Pencil, Trash2 };
  private readonly api = inject(BusinessApiService);
  readonly notification = inject(NotificationService);
  @Input({ required: true }) businessId!: number;
  @Input() initialMethods: FulfillmentMethod[] = [];
  @Output() close = new EventEmitter<void>();
  fulfillmentMethods: FulfillmentMethod[] = [];
  editingMethod: FulfillmentMethod | null = null;
  methodInput = '';
  feeInput: number | null = null;
  requiresAddressInput = false;

  ngOnInit(): void { this.fulfillmentMethods = [...this.initialMethods].sort((a, b) => a.position - b.position); }

  startEdit(method: FulfillmentMethod): void {
    this.editingMethod = method;
    this.methodInput = method.name;
    this.feeInput = this.clampFee(method.fee);
    this.requiresAddressInput = method.requires_address;
  }

  cancelEdit(): void {
    this.editingMethod = null;
    this.methodInput = '';
    this.feeInput = null;
    this.requiresAddressInput = false;
  }

  submit(): void {
    if (this.editingMethod) this.saveEdit(this.editingMethod);
    else this.addFulfillmentMethod();
  }

  deleteFulfillmentMethod(method: FulfillmentMethod): void {
    if (this.fulfillmentMethods.length === 1 || !this.businessId) return;
    this.api.deleteFulfillmentMethod(this.businessId, method.id).subscribe({
      next: () => {
        this.fulfillmentMethods = this.fulfillmentMethods.filter(item => item.id !== method.id);
        if (this.editingMethod?.id === method.id) this.cancelEdit();
      },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo eliminar el método de pedido.')
    });
  }

  clampFee(value: number | string | null | undefined): number | null {
    if (value == null || value === '') return null;
    const number = Number(value);
    return Number.isFinite(number) ? Math.max(0, number) : null;
  }

  private addFulfillmentMethod(): void {
    const name = this.methodInput.trim();
    if (!name || !this.businessId) return;
    this.api.addFulfillmentMethod(this.businessId, name, this.clampFee(this.feeInput), this.requiresAddressInput).subscribe({
      next: method => { this.fulfillmentMethods = [...this.fulfillmentMethods, method]; this.cancelEdit(); },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo agregar el método de pedido.')
    });
  }

  private saveEdit(method: FulfillmentMethod): void {
    const name = this.methodInput.trim();
    if (!name || !this.businessId) return;
    const fee = this.clampFee(this.feeInput);
    this.api.updateFulfillmentMethod(this.businessId, method.id, { name, fee, requires_address: this.requiresAddressInput }).subscribe({
      next: saved => { Object.assign(method, saved); this.cancelEdit(); },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo actualizar el método de pedido.')
    });
  }
}
