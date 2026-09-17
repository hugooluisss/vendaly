import { groupDirectoryBusinesses } from './public.component';
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
});
