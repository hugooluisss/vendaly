import { CatalogPageComponent } from './catalog-page.component';
import { PublicProduct } from '../public.models';

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
