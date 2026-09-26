import { CatalogManagementComponent, hasInvalidPricedOptions } from './catalog-management.component';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { NotificationService } from '../../shared/notification.service';
import { BusinessApiService } from '../business-api.service';
import { CategoryApiService } from '../category-api.service';
import { ProductApiService } from '../product-api.service';
import { of, throwError } from 'rxjs';

describe('catalog option editing', () => {
  function component(): CatalogManagementComponent { const instance = Object.create(CatalogManagementComponent.prototype) as CatalogManagementComponent; instance.product = { category_id: 1, name: 'Cafe', options: [] }; return instance; }
  it('adds a group and a value with a price delta', () => { const instance = component(); instance.addOption(); instance.addOptionValue(instance.product.options![0]); instance.product.options![0].values[0].price_delta = 15; expect(instance.product.options![0].values[0].price_delta).toBe(15); });
  it('guards priced options on a priceless product', () => { const instance = component(); instance.addOption(); instance.addOptionValue(instance.product.options![0]); instance.product.options![0].values[0].price_delta = 15; expect(hasInvalidPricedOptions(instance.product)).toBeTrue(); });
  it('keeps priced-option validation inline without a notification', () => { const instance = component(); const notification = jasmine.createSpyObj<NotificationService>('NotificationService', ['success', 'error']); const internals = instance as unknown as { notification: NotificationService; productsApi: { create: jasmine.Spy } }; internals.notification = notification; internals.productsApi = { create: jasmine.createSpy().and.returnValue(of({})) }; instance.product = { category_id: 1, name: 'Cafe', price: null, options: [{ name: 'Extras', selection_type: 'single', required: false, values: [{ name: 'Queso', price_delta: 15 }] }] }; instance.businessId = 1; instance.saveProduct(); expect(instance.productError).toBe('Los extras con precio requieren un producto con precio.'); expect(notification.error).not.toHaveBeenCalled(); });
  it('notifies for the coordinates guard and publish failures', () => { const instance = component(); const notification = jasmine.createSpyObj<NotificationService>('NotificationService', ['success', 'error']); const internals = instance as unknown as { notification: NotificationService; businessApi: { publish: jasmine.Spy } }; internals.notification = notification; instance.published = false; instance.hasCoordinates = false; instance.togglePublish(); expect(notification.error).toHaveBeenCalledWith(instance.coordinatesRequiredMessage); instance.hasCoordinates = true; internals.businessApi = { publish: jasmine.createSpy().and.returnValue(throwError(() => ({ error: 'Set a location on the map before publishing.' }))) }; instance.togglePublish(); expect(notification.error).toHaveBeenCalledWith(instance.coordinatesRequiredMessage); internals.businessApi.publish.and.returnValue(throwError(() => new Error('failed'))); instance.togglePublish(); expect(notification.error).toHaveBeenCalledWith('No se pudo actualizar la publicación del catálogo.'); });
  it('returns only the current page slice, capped at pageSize', () => { const instance = component(); instance.pageSize = 10; instance.products = Array.from({ length: 23 }, (_, index) => ({ id: index + 1, category_id: 1, name: `Producto ${index + 1}`, is_active: true, position: index, ingredients: [], options: [] })); instance.currentPage = 2; expect(instance.pagedProducts.length).toBe(instance.pageSize); expect(instance.pagedProducts.map(product => product.id)).toEqual([11, 12, 13, 14, 15, 16, 17, 18, 19, 20]); instance.currentPage = 3; expect(instance.pagedProducts.map(product => product.id)).toEqual([21, 22, 23]); });
  it('resets the current page when products are reloaded', () => { const instance = component(); const internals = instance as unknown as { categoriesApi: { list: jasmine.Spy }; productsApi: { list: jasmine.Spy } }; internals.categoriesApi = { list: jasmine.createSpy().and.returnValue(of([])) }; internals.productsApi = { list: jasmine.createSpy().and.returnValue(of([])) }; instance.businessId = 1; instance.currentPage = 3; instance.load(); expect(instance.currentPage).toBe(1); });
});

describe('catalog product row actions', () => {
  it('wires the icon buttons to editProduct and askDelete', async () => {
    TestBed.configureTestingModule({
      imports: [CatalogManagementComponent],
      providers: [
        provideRouter([]),
        { provide: BusinessApiService, useValue: { getMine: () => of({ business: { id: 1 } }) } },
        { provide: CategoryApiService, useValue: { list: () => of([]) } },
        { provide: ProductApiService, useValue: { list: () => of([{ id: 9, category_id: 1, name: 'Taco', is_active: true, position: 0, ingredients: [], options: [] }]) } },
        { provide: NotificationService, useValue: { success: () => undefined, error: () => undefined } },
      ],
    });
    const fixture = TestBed.createComponent(CatalogManagementComponent);
    fixture.detectChanges();
    const edit = spyOn(fixture.componentInstance, 'editProduct');
    const askDelete = spyOn(fixture.componentInstance, 'askDelete');
    const buttons = fixture.nativeElement.querySelectorAll('.products .row-actions button') as NodeListOf<HTMLButtonElement>;
    expect(buttons.length).toBe(2);
    expect(buttons[0].getAttribute('aria-label')).toBe('Editar producto');
    expect(buttons[1].getAttribute('aria-label')).toBe('Eliminar producto');
    expect(buttons[0].textContent?.trim()).toBe('');
    expect(buttons[1].textContent?.trim()).toBe('');
    buttons[0].click();
    buttons[1].click();
    expect(edit).toHaveBeenCalledWith(jasmine.objectContaining({ id: 9 }));
    expect(askDelete).toHaveBeenCalledWith('product', jasmine.objectContaining({ id: 9 }));
  });
});
