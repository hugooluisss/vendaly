import { of, throwError } from 'rxjs';
import { FulfillmentMethodsModalComponent } from './fulfillment-methods-modal.component';
import { FulfillmentMethod } from '../../business.models';

const method = (id: number, name: string): FulfillmentMethod => ({ id, name, position: id - 1, fee: null, requires_address: false });

describe('fulfillment methods modal', () => {
  function component(): FulfillmentMethodsModalComponent {
    const value = Object.create(FulfillmentMethodsModalComponent.prototype) as FulfillmentMethodsModalComponent;
    const api = {
      addFulfillmentMethod: jasmine.createSpy('addFulfillmentMethod').and.returnValue(of(method(2, 'Envío'))),
      updateFulfillmentMethod: jasmine.createSpy('updateFulfillmentMethod').and.callFake((_businessId: number, _methodId: number, fields: Partial<FulfillmentMethod>) => of({ ...method(2, 'Envío'), ...fields })),
      deleteFulfillmentMethod: jasmine.createSpy('deleteFulfillmentMethod').and.returnValue(of(undefined)),
    };
    Object.assign(value, { api, notification: { error: jasmine.createSpy('error') }, businessId: 4, fulfillmentMethods: [method(1, 'Consumo en el local')], methodInput: ' Envío ', feeInput: 25, requiresAddressInput: true });
    return value;
  }

  it('adds a named method with its fee and address requirement', () => {
    const value = component();
    value.addFulfillmentMethod();
    expect((value as any).api.addFulfillmentMethod).toHaveBeenCalledWith(4, 'Envío', 25, true);
    expect(value.fulfillmentMethods.map(item => item.name)).toEqual(['Consumo en el local', 'Envío']);
    expect(value.methodInput).toBe('');
  });

  it('updates the name, fee, and address requirement', () => {
    const value = component();
    const item = method(2, 'Envío'); item.name = 'Envío local'; item.fee = -5; item.requires_address = true;
    value.updateFulfillmentMethod(item);
    expect((value as any).api.updateFulfillmentMethod).toHaveBeenCalledWith(4, 2, { name: 'Envío local', fee: 0, requires_address: true });
  });

  it('deletes methods when another remains and blocks deleting the last one', () => {
    const value = component(); value.fulfillmentMethods.push(method(2, 'Envío'));
    value.deleteFulfillmentMethod(value.fulfillmentMethods[1]);
    expect(value.fulfillmentMethods.length).toBe(1);
    value.deleteFulfillmentMethod(value.fulfillmentMethods[0]);
    expect((value as any).api.deleteFulfillmentMethod).toHaveBeenCalledTimes(1);
  });

  it('reports server errors for failed changes and clamps negative fees', () => {
    const value = component();
    expect(value.clampFee(-3)).toBe(0);
    (value as any).api.addFulfillmentMethod.and.returnValue(throwError(() => ({ error: { error: 'Rejected' } })));
    value.addFulfillmentMethod();
    expect((value as any).notification.error).toHaveBeenCalledWith('Rejected');
  });
});
