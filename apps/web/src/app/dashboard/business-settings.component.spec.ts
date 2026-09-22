import { BusinessSettingsComponent, unconfiguredDays } from './business-settings/business-settings.component';

describe('business hours helpers', () => { it('finds the days missing from the API response', () => expect(unconfiguredDays([{ day_of_week: 1 }, { day_of_week: 6 }])).toEqual([0, 2, 3, 4, 5])); });

describe('business fulfillment settings', () => {
  it('counts enabled methods so the last one can stay disabled in the UI', () => {
    const component = Object.create(BusinessSettingsComponent.prototype) as BusinessSettingsComponent;
    component.fulfillment = { pickup_enabled: true, delivery_enabled: false, dine_in_enabled: false };
    expect(component.enabledCount()).toBe(1);
  });
  it('keeps fees available for the round trip', () => {
    const component = Object.create(BusinessSettingsComponent.prototype) as BusinessSettingsComponent;
    component.fulfillment = { pickup_enabled: true, delivery_enabled: true, dine_in_enabled: false, pickup_fee: null, delivery_fee: 30, dine_in_fee: null };
    expect(component.fulfillment.delivery_fee).toBe(30);
  });
  it('blocks deleting the last payment method client-side', () => {
    const component = Object.create(BusinessSettingsComponent.prototype) as BusinessSettingsComponent;
    component.paymentMethods = [{ id: 1, name: 'Efectivo', position: 0 }]; component.businessId = 4;
    component.deletePaymentMethod(component.paymentMethods[0]);
    expect(component.paymentMethods.length).toBe(1);
  });
});
