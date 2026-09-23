import { Injectable } from '@angular/core';
import Swal from 'sweetalert2';

@Injectable({ providedIn: 'root' })
export class NotificationService {
  success(message: string): void {
    void Swal.fire({
      title: message,
      icon: 'success',
      toast: true,
      position: 'top-end',
      timer: 2500,
      showConfirmButton: false
    });
  }

  error(message: string): void {
    void Swal.fire({
      title: message,
      icon: 'error',
      showConfirmButton: true
    });
  }
}
