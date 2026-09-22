import { CustomerPhoneService } from './customer-phone.service';

describe('CustomerPhoneService', () => {
  beforeEach(() => localStorage.removeItem('vendaly.customer-phone'));

  it('persists and hydrates the last phone', () => {
    new CustomerPhoneService().persist('+52 55 1234 5678');
    expect(new CustomerPhoneService().load()).toBe('+52 55 1234 5678');
  });

  it('returns an empty phone when storage is unavailable', () => {
    expect(new CustomerPhoneService().load()).toBe('');
    spyOn(Storage.prototype, 'getItem').and.throwError('blocked');
    expect(new CustomerPhoneService().load()).toBe('');
  });

  it('does not throw when storage rejects a write', () => {
    spyOn(Storage.prototype, 'setItem').and.throwError('blocked');
    expect(() => new CustomerPhoneService().persist('+52 55 1234 5678')).not.toThrow();
  });
});
