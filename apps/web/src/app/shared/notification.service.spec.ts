import Swal from 'sweetalert2';
import { NotificationService } from './notification.service';

describe('NotificationService', () => {
  it('shows a success toast with the message', () => {
    const fire = spyOn(Swal, 'fire').and.resolveTo({} as never);
    new NotificationService().success('Guardado con éxito.');
    expect(fire).toHaveBeenCalledWith(jasmine.objectContaining({
      title: 'Guardado con éxito.', icon: 'success', toast: true,
      position: 'top-end', timer: 2500, showConfirmButton: false
    }));
  });

  it('shows an acknowledged error modal with the message', () => {
    const fire = spyOn(Swal, 'fire').and.resolveTo({} as never);
    new NotificationService().error('No se pudo guardar.');
    expect(fire).toHaveBeenCalledWith(jasmine.objectContaining({
      title: 'No se pudo guardar.', icon: 'error', showConfirmButton: true
    }));
  });
});
