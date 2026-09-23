import { CatalogPageComponent } from './catalog-page.component';
import { PublicProduct } from '../public.models';
import { ActivatedRoute, Router } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { CatalogApiService } from '../catalog-api.service';
import { CartService } from '../cart.service';
import { of, throwError } from 'rxjs';

const plain: PublicProduct = { id: 1, name: 'Torta', price: 80, ingredients: [] };
const configured: PublicProduct = { id: 2, name: 'Café', price: 50, ingredients: [], options: [{ id: 1, name: 'Tamaño', selection_type: 'single', required: true, values: [{ id: 9, name: 'Grande', price_delta: 10 }] }] };

describe('CatalogPageComponent product configuration', () => {
  let component: CatalogPageComponent;
  let added: unknown[][];

  beforeEach(() => {
    component = Object.create(CatalogPageComponent.prototype) as CatalogPageComponent;
    added = [];
    Object.assign(component, { cart: { add: (...args: unknown[]) => added.push(args) } });
    component.selections = new Map();
    component.pendingProduct = null;
  });

  it('adds products without options directly', () => {
    component.addProduct(plain);
    expect(added).toEqual([[plain]]);
    expect(component.pendingProduct).toBeNull();
  });

  it('opens option products and blocks confirmation until required selection', () => {
    component.addProduct(configured);
    expect(component.pendingProduct).toBe(configured);
    component.confirmAdd();
    expect(added).toEqual([]);
    component.toggle(configured, 9, true);
    component.confirmAdd();
    expect(added).toEqual([[configured, [9]]]);
    expect(component.pendingProduct).toBeNull();
  });
});

describe('CatalogPageComponent QR scan tracking', () => {
  it('records a scan only when src=qr and does not let a failed POST affect catalog loading', () => {
    const recordScan = jasmine.createSpy('recordScan').and.returnValue(throwError(() => new Error('offline')));
    const get = jasmine.createSpy('get').and.returnValue(of(null));
    TestBed.configureTestingModule({ providers: [
      { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => 'tienda' }, queryParamMap: { get: (key: string) => key === 'src' ? 'qr' : null } } } },
      { provide: Router, useValue: { navigate: jasmine.createSpy('navigate') } },
      { provide: CatalogApiService, useValue: { get, recordScan } },
      { provide: CartService, useValue: { add: jasmine.createSpy('add'), items: [], total: () => 0 } },
    ] });
    expect(() => TestBed.runInInjectionContext(() => new CatalogPageComponent())).not.toThrow();
    expect(recordScan).toHaveBeenCalledOnceWith('tienda');
    expect(get).toHaveBeenCalledOnceWith('tienda');
  });

  it('does not record a scan without the QR marker', () => {
    const recordScan = jasmine.createSpy('recordScan').and.returnValue(of(undefined));
    TestBed.configureTestingModule({ providers: [
      { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => 'tienda' }, queryParamMap: { get: () => null } } } },
      { provide: Router, useValue: { navigate: jasmine.createSpy('navigate') } },
      { provide: CatalogApiService, useValue: { get: jasmine.createSpy('get').and.returnValue(of(null)), recordScan } },
      { provide: CartService, useValue: { add: jasmine.createSpy('add'), items: [], total: () => 0 } },
    ] });
    TestBed.runInInjectionContext(() => new CatalogPageComponent());
    expect(recordScan).not.toHaveBeenCalled();
  });
});
