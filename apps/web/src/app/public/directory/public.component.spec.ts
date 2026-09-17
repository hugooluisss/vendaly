import { groupDirectoryBusinesses, PublicComponent } from './public.component';
import { DirectoryBusiness } from '../public.models';

const business = (name: string, category?: string | null): DirectoryBusiness => ({ name, slug: name.toLowerCase(), category });

describe('groupDirectoryBusinesses', () => {
  it('orders populated categories and puts uncategorized businesses last', () => {
    const groups = groupDirectoryBusinesses([
      business('Sin categoría', null),
      business('Tienda', 'store'),
      business('Café', 'cafe'),
      business('Restaurante', 'restaurant'),
    ]);

    expect(groups.map(group => group.label)).toEqual(['Restaurante', 'Café', 'Tienda', 'Otros']);
    expect(groups[3].businesses.map(item => item.name)).toEqual(['Sin categoría']);
  });

  it('omits empty categories', () => {
    expect(groupDirectoryBusinesses([business('Café', 'cafe')]).map(group => group.label)).toEqual(['Café']);
  });

  it('supports no, one, and multiple active category filters in category order', () => {
    const businesses = [business('Tienda', 'store'), business('Café', 'cafe'), business('Restaurante', 'restaurant')];
    const component = Object.create(PublicComponent.prototype) as PublicComponent;
    component.businesses = businesses;
    component.activeCategories = new Set();
    component['updateGroups']();

    expect(component.categoryGroups.map(group => group.label)).toEqual(['Restaurante', 'Café', 'Tienda']);
    component.activeCategories = new Set(['cafe']);
    component['updateGroups']();
    expect(component.categoryGroups.map(group => group.label)).toEqual(['Café']);
    component.activeCategories = new Set(['store', 'restaurant']);
    component['updateGroups']();
    expect(component.categoryGroups.map(group => group.label)).toEqual(['Restaurante', 'Tienda']);
  });
});

describe('PublicComponent pagination', () => {
  it('reveals 18, then 18, then the remaining 4 businesses', async () => {
    const component = Object.create(PublicComponent.prototype) as PublicComponent;
    component.categoryGroups = [{ label: 'Café', businesses: Array.from({ length: 40 }, (_, index) => business(`Business ${index}`, 'cafe')) }];
    component.visibleCount = 18;

    expect(component.visibleBusinesses).toHaveSize(18);
    component.loadMore();
    expect(component.visibleBusinesses).toHaveSize(36);
    await Promise.resolve();
    component.loadMore();
    expect(component.visibleBusinesses).toHaveSize(40);
  });

  it('reveals all small result sets and does not load past the end', () => {
    const component = Object.create(PublicComponent.prototype) as PublicComponent;
    component.categoryGroups = [{ label: 'Café', businesses: Array.from({ length: 10 }, (_, index) => business(`Business ${index}`, 'cafe')) }];
    component.visibleCount = 18;

    expect(component.visibleBusinesses).toHaveSize(10);
    component.loadMore();
    expect(component.visibleBusinesses).toHaveSize(10);
  });
});
