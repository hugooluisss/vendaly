import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { BusinessApiService } from '../business-api.service';
import { OrderApiService } from '../order-api.service';
import { OrderHistoryComponent } from './order-history.component';

describe('OrderHistoryComponent', () => {
  let fixture: ComponentFixture<OrderHistoryComponent>;
  let component: OrderHistoryComponent;
  let ordersApi: jasmine.SpyObj<OrderApiService>;

  const order = {
    id: 7,
    order_number: 12,
    created_at: '2026-09-22T12:00:00Z',
    status: { id: 1, name: 'Creado', color: '#EA580C' },
    customer_phone: '+52 55 1234 5678',
    items: [{ name: 'Taco', quantity: 1 }],
    total: 50
  };

  beforeEach(async () => {
    ordersApi = jasmine.createSpyObj<OrderApiService>('OrderApiService', ['list', 'updateStatus']);
    ordersApi.list.and.returnValue(of({ orders: [{ ...order, status: { ...order.status }, items: [...order.items] }], count: 1 }));
    ordersApi.updateStatus.and.returnValue(of(void 0));
    await TestBed.configureTestingModule({
      imports: [OrderHistoryComponent],
      providers: [
        { provide: BusinessApiService, useValue: { getMine: () => of({ business: { id: 4 } }), orderStatuses: () => of([{ id: 1, name: 'Creado', color: '#EA580C' }, { id: 2, name: 'Entregado', color: '#16A34A' }]) } },
        { provide: OrderApiService, useValue: ordersApi }
      ]
    }).compileComponents();
    fixture = TestBed.createComponent(OrderHistoryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('renders the order number and colored status badge', () => {
    const element = fixture.nativeElement as HTMLElement;
    expect(element.textContent).toContain('Pedido #12');
    const badge = element.querySelector('.status') as HTMLElement;
    expect(badge.textContent).toContain('Creado');
    expect(badge.style.backgroundColor).toBe('rgb(234, 88, 12)');
  });

  it('shows the customer phone when present', () => {
    expect((fixture.nativeElement as HTMLElement).querySelector('.customer-phone')?.textContent).toContain('+52 55 1234 5678');
  });

  it('omits the phone for historical orders without a customer', () => {
    component.orders = [{ ...component.orders[0], customer_phone: null }, { ...component.orders[0], id: 8, customer_phone: undefined }];
    fixture.detectChanges();
    expect((fixture.nativeElement as HTMLElement).querySelectorAll('.customer-phone').length).toBe(0);
  });

  it('changes status through the API and updates the view', () => {
    component.changeStatus(component.orders[0], 2);
    fixture.detectChanges();

    expect(ordersApi.updateStatus).toHaveBeenCalledWith(4, 7, 2);
    expect(component.orders[0].status.name).toBe('Entregado');
    expect((fixture.nativeElement as HTMLElement).querySelector('.status')?.textContent).toContain('Entregado');
  });

  it('surfaces an API error without losing the previous status', () => {
    ordersApi.updateStatus.and.returnValue(throwError(() => new Error('failed')));
    component.changeStatus(component.orders[0], 2);

    expect(component.error).toBe('No se pudo actualizar el estado del pedido.');
    expect(component.orders[0].status.name).toBe('Creado');
  });
});
