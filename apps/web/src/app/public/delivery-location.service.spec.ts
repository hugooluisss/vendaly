import { DeliveryLocationService } from './delivery-location.service';

describe('DeliveryLocationService', () => {
  beforeEach(() => localStorage.removeItem('vendaly.delivery-location'));
  it('loads and stores the last location', () => {
    const service = new DeliveryLocationService();
    service.persist({ address: 'Roma', position: { lat: 19.4, lng: -99.1 } });
    expect(service.load()).toEqual({ address: 'Roma', position: { lat: 19.4, lng: -99.1 } });
  });
  it('returns an empty location when absent or storage throws', () => {
    expect(new DeliveryLocationService().load()).toEqual({ address: '' });
    spyOn(Storage.prototype, 'getItem').and.throwError('blocked');
    expect(new DeliveryLocationService().load()).toEqual({ address: '' });
  });
});
