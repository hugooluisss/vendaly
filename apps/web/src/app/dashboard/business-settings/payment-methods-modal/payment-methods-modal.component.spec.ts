import { PaymentMethodsModalComponent } from './payment-methods-modal.component';
import { PaymentMethod } from '../../business.models';

const method = (id: number, name: string): PaymentMethod => ({ id, name, position: id - 1 });

describe('payment methods modal', () => {
  it('blocks deleting the last payment method client-side', () => {
    const component = Object.create(PaymentMethodsModalComponent.prototype) as PaymentMethodsModalComponent;
    component.paymentMethods = [method(1, 'Efectivo')]; component.businessId = 4;
    component.deletePaymentMethod(component.paymentMethods[0]);
    expect(component.paymentMethods.length).toBe(1);
  });
});
