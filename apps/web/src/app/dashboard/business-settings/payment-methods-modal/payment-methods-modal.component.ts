import { Component, EventEmitter, Input, OnInit, Output, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, ArrowUp, ArrowDown, Pencil, Trash2 } from 'lucide-angular';
import { BusinessApiService } from '../../business-api.service';
import { PaymentMethod } from '../../business.models';
import { ModalComponent } from '../../../shared/modal/modal.component';
import { NotificationService } from '../../../shared/notification.service';

@Component({ selector: 'app-payment-methods-modal', standalone: true, imports: [FormsModule, ModalComponent, LucideAngularModule], styleUrl: './payment-methods-modal.component.css', templateUrl: './payment-methods-modal.component.html' })
export class PaymentMethodsModalComponent implements OnInit {
  readonly icons = { ArrowUp, ArrowDown, Pencil, Trash2 };
  private readonly api = inject(BusinessApiService);
  readonly notification = inject(NotificationService);
  @Input({ required: true }) businessId!: number;
  @Input() initialMethods: PaymentMethod[] = [];
  @Output() close = new EventEmitter<void>();
  paymentMethods: PaymentMethod[] = [];
  editingMethod: PaymentMethod | null = null;
  paymentMethodInput = '';

  ngOnInit(): void { this.paymentMethods = [...this.initialMethods].sort((a, b) => a.position - b.position); }

  startEdit(method: PaymentMethod): void {
    this.editingMethod = method;
    this.paymentMethodInput = method.name;
  }

  cancelEdit(): void {
    this.editingMethod = null;
    this.paymentMethodInput = '';
  }

  submit(): void {
    if (this.editingMethod) this.saveEdit(this.editingMethod);
    else this.addPaymentMethod();
  }

  addPaymentMethod(): void {
    const name = this.paymentMethodInput.trim();
    if (!name || !this.businessId) return;
    this.api.addPaymentMethod(this.businessId, name).subscribe({
      next: method => { this.paymentMethods = [...this.paymentMethods, method]; this.cancelEdit(); },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo agregar el método de pago.')
    });
  }

  deletePaymentMethod(method: PaymentMethod): void {
    if (this.paymentMethods.length === 1 || !this.businessId) return;
    this.api.deletePaymentMethod(this.businessId, method.id).subscribe({
      next: () => {
        this.paymentMethods = this.paymentMethods.filter(item => item.id !== method.id);
        if (this.editingMethod?.id === method.id) this.cancelEdit();
      },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo eliminar el método de pago.')
    });
  }

  movePaymentMethod(method: PaymentMethod, direction: -1 | 1): void { const index = this.paymentMethods.indexOf(method); const next = index + direction; if (next < 0 || next >= this.paymentMethods.length || !this.businessId) return; [this.paymentMethods[index], this.paymentMethods[next]] = [this.paymentMethods[next], this.paymentMethods[index]]; this.api.reorderPaymentMethods(this.businessId, this.paymentMethods.map(item => item.id)).subscribe({ error: response => this.notification.error(response.error?.error ?? 'No se pudo reordenar los métodos de pago.') }); }

  private saveEdit(method: PaymentMethod): void {
    const name = this.paymentMethodInput.trim();
    if (!name || !this.businessId) return;
    this.api.renamePaymentMethod(this.businessId, method.id, name).subscribe({
      next: saved => { Object.assign(method, saved); this.cancelEdit(); },
      error: response => this.notification.error(response.error?.error ?? 'No se pudo renombrar el método de pago.')
    });
  }
}
