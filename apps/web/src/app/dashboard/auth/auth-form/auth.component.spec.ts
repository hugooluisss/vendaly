import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { AuthService } from '../../../auth/auth.service';
import { NotificationService } from '../../../shared/notification.service';
import { AuthComponent } from './auth.component';

describe('AuthComponent', () => {
  it('notifies when login fails and leaves form validation local', () => {
    const auth = jasmine.createSpyObj<AuthService>('AuthService', ['login', 'register']);
    auth.login.and.returnValue(throwError(() => new Error('failed')));
    auth.register.and.returnValue(of({ accessToken: 'token', refreshToken: 'refresh' }));
    const notification = jasmine.createSpyObj<NotificationService>('NotificationService', ['success', 'error']);
    TestBed.configureTestingModule({ providers: [
      { provide: AuthService, useValue: auth },
      { provide: NotificationService, useValue: notification },
      { provide: Router, useValue: { navigate: jasmine.createSpy() } },
      { provide: ActivatedRoute, useValue: { snapshot: { data: { mode: 'login' } } } }
    ] });
    const component = TestBed.runInInjectionContext(() => new AuthComponent());
    component.form.setValue({ email: 'owner@example.com', password: 'password123' });
    component.submit();
    expect(notification.error).toHaveBeenCalledWith('No se pudo completar la autenticación.');
    expect(component.submitting).toBeFalse();
    component.form.controls.email.setValue('bad');
    expect(component.form.invalid).toBeTrue();
  });
});
