import { PublicComponent } from './public.component';
import { DirectoryBusiness } from '../public.models';

const business = (name: string, category?: string | null): DirectoryBusiness => ({ name, slug: name.toLowerCase(), category });

describe('PublicComponent pagination', () => {
  it('reveals 10, then 20, then the remaining 5 businesses', async () => {
    const component = Object.create(PublicComponent.prototype) as PublicComponent;
    component.businesses = Array.from({ length: 25 }, (_, index) => business(`Business ${index}`, 'cafe'));
    component.activeCategories = new Set();
    component.visibleCount = 10;

    expect(component.visibleBusinesses).toHaveSize(10);
    component.loadMore();
    expect(component.visibleBusinesses).toHaveSize(20);
    await Promise.resolve();
    component.loadMore();
    expect(component.visibleBusinesses).toHaveSize(25);
  });

  it('reveals all small result sets and does not load past the end', () => {
    const component = Object.create(PublicComponent.prototype) as PublicComponent;
    component.businesses = Array.from({ length: 10 }, (_, index) => business(`Business ${index}`, 'cafe'));
    component.activeCategories = new Set();
    component.visibleCount = 10;

    expect(component.visibleBusinesses).toHaveSize(10);
    component.loadMore();
    expect(component.visibleBusinesses).toHaveSize(10);
  });

  it('narrows the flat list with one or more category chips', () => {
    const component = Object.create(PublicComponent.prototype) as PublicComponent;
    component.businesses = [business('Tienda', 'store'), business('Café', 'cafe'), business('Restaurante', 'restaurant')];
    component.activeCategories = new Set(['cafe', 'store']);

    expect(component.visibleBusinesses.map(item => item.name)).toEqual(['Tienda', 'Café']);
  });

  it('combines the name-search result with category filtering', () => {
    const component = Object.create(PublicComponent.prototype) as PublicComponent;
    component.businesses = [business('Café Centro', 'cafe'), business('Café Norte', 'cafe'), business('Tienda Centro', 'store')];
    component.activeCategories = new Set(['cafe']);

    expect(component.visibleBusinesses.map(item => item.name)).toEqual(['Café Centro', 'Café Norte']);
  });
});
