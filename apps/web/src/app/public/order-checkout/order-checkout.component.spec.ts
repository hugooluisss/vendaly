import { Subject } from 'rxjs';
import { OrderCheckoutComponent } from './order-checkout.component';

describe('OrderCheckoutComponent', () => {
  function component(): OrderCheckoutComponent { const value = Object.create(OrderCheckoutComponent.prototype) as OrderCheckoutComponent; Object.assign(value, { cart: { total: () => 100, items: [{}] }, selectedMethod: '', selectedPaymentMethod: null, customerPhone: '', deliveryAddress: '', deliveryPosition: undefined }); return value; }
  it('adds the selected fulfillment fee to the total', () => { const value = component(); value.selectedMethod = 'delivery'; expect(value.selectedFee([{ type: 'delivery', fee: 30 }])).toBe(30); expect(value.grandTotal([{ type: 'delivery', fee: 30 }])).toBe(130); });
  it('requires phone, fulfillment, and payment before submit', () => { const value = component(); expect(value.canSubmit()).toBeFalse(); value.selectedMethod = 'pickup'; value.selectedPaymentMethod = 2; expect(value.canSubmit()).toBeFalse(); value.customerPhone = '   '; expect(value.canSubmit()).toBeFalse(); value.customerPhone = '+52 55 1234 5678'; expect(value.canSubmit()).toBeTrue(); });
  it('requires an address or pin for delivery', () => { const value = component(); value.customerPhone = '+52 55 1234 5678'; value.selectedMethod = 'delivery'; value.selectedPaymentMethod = 2; expect(value.canSubmit()).toBeFalse(); value.deliveryAddress = 'Roma'; expect(value.canSubmit()).toBeTrue(); });
  it('remembers the submitted phone only after order creation succeeds', () => {
    const value = component(); const result = new Subject<{ whatsapp_url: string }>(); const persist = jasmine.createSpy('persist'); const create = jasmine.createSpy('create').and.returnValue(result.asObservable());
    Object.assign(value, { slug: 'taqueria', selectedMethod: 'pickup', selectedPaymentMethod: 2, customerPhone: '+52 55 1234 5678', orders: { create }, phones: { persist } });
    spyOn(window, 'open');
    value.submit();
    expect(create).toHaveBeenCalledWith('taqueria', value.cart.items, 100, 'pickup', { address: '', position: undefined }, 2, '+52 55 1234 5678');
    expect(persist).not.toHaveBeenCalled();
    result.next({ whatsapp_url: 'https://wa.me/123' });
    expect(persist).toHaveBeenCalledOnceWith('+52 55 1234 5678');
  });
});
