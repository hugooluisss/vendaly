import { Subject } from 'rxjs';
import { OrderCheckoutComponent } from './order-checkout.component';
import { PublicFulfillmentMethod } from '../public.models';

describe('OrderCheckoutComponent', () => {
  function component(): OrderCheckoutComponent {
    const value = Object.create(OrderCheckoutComponent.prototype) as OrderCheckoutComponent;
    Object.assign(value, { cart: { total: () => 100, items: [{}] }, fulfillmentMethods: [], selectedMethod: null, selectedPaymentMethod: null, customerPhone: '', deliveryAddress: '', deliveryPosition: undefined });
    return value;
  }
  const methods: PublicFulfillmentMethod[] = [
    { id: 5, name: 'Consumo en el local', fee: null, requires_address: false },
    { id: 8, name: 'Envío foráneo', fee: 30, requires_address: true },
  ];

  it('adds the selected method fee to the total by id', () => { const value = component(); value.fulfillmentMethods = methods; value.selectedMethod = 8; expect(value.selectedFee()).toBe(30); expect(value.grandTotal()).toBe(130); });
  it('requires phone, fulfillment, and payment before submit', () => { const value = component(); value.fulfillmentMethods = methods; expect(value.canSubmit()).toBeFalse(); value.selectedMethod = 5; value.selectedPaymentMethod = 2; expect(value.canSubmit()).toBeFalse(); value.customerPhone = '   '; expect(value.canSubmit()).toBeFalse(); value.customerPhone = '+52 55 1234 5678'; expect(value.canSubmit()).toBeTrue(); });
  it('requires an address or pin only for a method configured to require one', () => { const value = component(); value.fulfillmentMethods = methods; value.customerPhone = '+52 55 1234 5678'; value.selectedPaymentMethod = 2; value.selectedMethod = 8; expect(value.selectedMethodRequiresAddress).toBeTrue(); expect(value.canSubmit()).toBeFalse(); value.deliveryAddress = 'Roma'; expect(value.canSubmit()).toBeTrue(); value.deliveryAddress = ''; value.deliveryPosition = { lat: 19.4, lng: -99.1 }; expect(value.canSubmit()).toBeTrue(); value.selectedMethod = 5; value.deliveryPosition = undefined; expect(value.selectedMethodRequiresAddress).toBeFalse(); expect(value.canSubmit()).toBeTrue(); });
  it('sends the selected method id and remembers the phone only after order creation succeeds', () => {
    const value = component(); const result = new Subject<{ whatsapp_url: string }>(); const persist = jasmine.createSpy('persist'); const create = jasmine.createSpy('create').and.returnValue(result.asObservable());
    Object.assign(value, { slug: 'taqueria', fulfillmentMethods: methods, selectedMethod: 5, selectedPaymentMethod: 2, customerPhone: '+52 55 1234 5678', orders: { create }, phones: { persist } });
    spyOn(window, 'open');
    value.submit();
    expect(create).toHaveBeenCalledWith('taqueria', value.cart.items, 100, 5, { address: '', position: undefined }, 2, '+52 55 1234 5678');
    expect(persist).not.toHaveBeenCalled();
    result.next({ whatsapp_url: 'https://wa.me/123' });
    expect(persist).toHaveBeenCalledOnceWith('+52 55 1234 5678');
  });
});
