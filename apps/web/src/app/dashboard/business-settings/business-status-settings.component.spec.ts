import { of, throwError } from 'rxjs';
import { BusinessSettingsComponent } from './business-settings.component';
import { OrderStatus } from '../business.models';

const status = (id: number, name: string, isDefault = false): OrderStatus => ({ id, name, color: '#EA580C', is_terminal: false, is_default: isDefault, position: id - 1 });

describe('business order status settings', () => {
  function component(api: Partial<Record<string, jasmine.Func>> = {}) {
    const result = Object.create(BusinessSettingsComponent.prototype) as BusinessSettingsComponent;
    result.businessId = 4; result.orderStatuses = [status(1, 'Creado', true), status(2, 'Listo')]; result.error = '';
    (result as unknown as { api: unknown }).api = api;
    return result;
  }

  it('round-trips status CRUD operations through the API', () => {
    const added = status(3, 'En camino'); const api = { addOrderStatus: jasmine.createSpy().and.returnValue(of(added)), renameOrderStatus: jasmine.createSpy().and.returnValue(of({ ...added, name: 'En ruta' })), recolorOrderStatus: jasmine.createSpy().and.returnValue(of({ ...added, color: '#123456' })), setOrderStatusTerminal: jasmine.createSpy().and.returnValue(of({ ...added, is_terminal: true })), reorderOrderStatuses: jasmine.createSpy().and.returnValue(of([])), deleteOrderStatus: jasmine.createSpy().and.returnValue(of(void 0)) };
    const result = component(api); result.orderStatusInput = 'En camino'; result.orderStatusColor = '#EA580C'; result.orderStatusTerminal = false; result.addOrderStatus(); expect(result.orderStatuses).toContain(added); added.name = 'En ruta'; result.renameOrderStatus(added); added.color = '#123456'; result.recolorOrderStatus(added); added.is_terminal = true; result.toggleOrderStatusTerminal(added); result.moveOrderStatus(added, -1); result.deleteOrderStatus(added); expect(api.addOrderStatus).toHaveBeenCalledWith(4, 'En camino', '#EA580C', false); expect(api.deleteOrderStatus).toHaveBeenCalledWith(4, 3); expect(result.orderStatuses.map(item => item.id)).toEqual([1, 2]);
  });

  it('unsets the old default in local state when a new one is selected', () => { const next = status(2, 'Listo'); const result = component({ setOrderStatusDefault: jasmine.createSpy().and.returnValue(of(next)) }); result.setDefaultOrderStatus(next); expect(result.orderStatuses[0].is_default).toBeFalse(); expect(result.orderStatuses[1].is_default).toBeTrue(); });
  it('blocks deleting the last status and the current default', () => { const api = { deleteOrderStatus: jasmine.createSpy().and.returnValue(of(void 0)) }; const result = component(api); result.deleteOrderStatus(result.orderStatuses[0]); expect(api.deleteOrderStatus).not.toHaveBeenCalled(); result.orderStatuses = [status(1, 'Creado', true)]; result.deleteOrderStatus(result.orderStatuses[0]); expect(api.deleteOrderStatus).not.toHaveBeenCalled(); });
  it('surfaces a raced server deletion rejection', () => { const result = component({ deleteOrderStatus: jasmine.createSpy().and.returnValue(throwError(() => ({ error: { error: 'El estado está en uso.' } }))) }); result.orderStatuses[1].is_default = false; result.deleteOrderStatus(result.orderStatuses[1]); expect(result.error).toBe('El estado está en uso.'); });
});
