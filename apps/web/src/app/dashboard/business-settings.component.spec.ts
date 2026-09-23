import { BusinessSettingsComponent, unconfiguredDays } from './business-settings/business-settings.component';
import { APP_BASE_HREF } from '@angular/common';
import { fakeAsync, flushMicrotasks, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of, throwError } from 'rxjs';
import { BusinessApiService } from './business-api.service';
import { Business } from './business.models';
import { NotificationService } from '../shared/notification.service';

describe('business public catalog URL', () => {
  function load(business?: Business, baseHref = '/') {
    TestBed.configureTestingModule({ providers: [provideRouter([]), { provide: APP_BASE_HREF, useValue: baseHref }, { provide: BusinessApiService, useValue: { getMine: () => business ? of({ business, hours: [] }) : throwError(() => new Error('No business')) } }] });
    return TestBed.runInInjectionContext(() => new BusinessSettingsComponent());
  }
  it('is unset without a business', () => expect(load().publicCatalogUrl).toBeUndefined());
  it('is unset without a slug', () => expect(load({ id: 1, name: 'Tienda' }).publicCatalogUrl).toBeUndefined());
  it('uses the current origin and root base href', () => expect(load({ id: 1, name: 'Tienda', slug: 'mi-tienda' }).publicCatalogUrl).toBe(`${window.location.origin}/public/catalog/mi-tienda`));
  it('includes the gateway base href', () => expect(load({ id: 1, name: 'Tienda', slug: 'mi-tienda' }, '/vendaly-app/').publicCatalogUrl).toBe(`${window.location.origin}/vendaly-app/public/catalog/mi-tienda`));
  it('renders no QR without a slug', () => { load({ id: 1, name: 'Tienda' }); const fixture = TestBed.createComponent(BusinessSettingsComponent); fixture.detectChanges(); expect(fixture.nativeElement.querySelector('.catalog-qr')).toBeNull(); });
  it('renders and downloads a PNG for a slugged business', fakeAsync(() => { load({ id: 1, name: 'Tienda', slug: 'mi-tienda' }); const fixture = TestBed.createComponent(BusinessSettingsComponent); fixture.detectChanges(); flushMicrotasks(); fixture.detectChanges(); const section = fixture.nativeElement.querySelector('.catalog-qr') as HTMLElement; const canvas = section.querySelector('canvas')!; expect(section.textContent).toContain(`${window.location.origin}/public/catalog/mi-tienda`); expect(canvas.width).toBeGreaterThan(0); expect(canvas.toDataURL('image/png')).toContain('data:image/png;base64,'); const click = spyOn(HTMLAnchorElement.prototype, 'click').and.stub(); (section.querySelector('button') as HTMLButtonElement).click(); expect(click).toHaveBeenCalled(); expect((click.calls.mostRecent().object as HTMLAnchorElement).download).toBe('catalogo-qr.png'); }));
});

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

describe('business profile save notifications', () => {
  function setup(update: jasmine.Spy) {
    const notification = jasmine.createSpyObj<NotificationService>('NotificationService', ['success', 'error']);
    TestBed.configureTestingModule({ providers: [
      { provide: BusinessApiService, useValue: { getMine: () => throwError(() => new Error('unused in this suite')), update } },
      { provide: NotificationService, useValue: notification }
    ] });
    const component = TestBed.runInInjectionContext(() => new BusinessSettingsComponent());
    component.businessId = 4;
    component.profile.setValue({ name: 'Tienda', whatsapp_number: '', description: '', category: null, location: '' });
    return { component, notification };
  }

  it('shows success after saving the profile', () => {
    const { component, notification } = setup(jasmine.createSpy().and.returnValue(of({})));
    component.saveProfile();
    expect(notification.success).toHaveBeenCalledWith('Perfil guardado con éxito.');
  });

  it('shows the server reason or existing fallback when saving fails', () => {
    const update = jasmine.createSpy().and.returnValue(throwError(() => ({ error: { error: 'Sin permiso.' } })));
    const { component, notification } = setup(update);
    component.saveProfile();
    expect(notification.error).toHaveBeenCalledWith('Sin permiso.');
  });
});
