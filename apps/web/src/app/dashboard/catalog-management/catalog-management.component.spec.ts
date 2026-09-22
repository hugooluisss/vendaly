import { CatalogManagementComponent, hasInvalidPricedOptions } from './catalog-management.component';

describe('catalog option editing', () => {
  function component(): CatalogManagementComponent { const instance = Object.create(CatalogManagementComponent.prototype) as CatalogManagementComponent; instance.product = { category_id: 1, name: 'Cafe', options: [] }; return instance; }
  it('adds a group and a value with a price delta', () => { const instance = component(); instance.addOption(); instance.addOptionValue(instance.product.options![0]); instance.product.options![0].values[0].price_delta = 15; expect(instance.product.options![0].values[0].price_delta).toBe(15); });
  it('guards priced options on a priceless product', () => { const instance = component(); instance.addOption(); instance.addOptionValue(instance.product.options![0]); instance.product.options![0].values[0].price_delta = 15; expect(hasInvalidPricedOptions(instance.product)).toBeTrue(); });
});
