import { TestBed } from '@angular/core/testing';
import { ActivatedRouteSnapshot, Router, RouterStateSnapshot } from '@angular/router';
import { AuthService } from './auth.service';
import { authGuard } from './auth.guard';

describe('authGuard', () => {
  it('blocks without a session', () => {
    const router = jasmine.createSpyObj<Router>('Router', ['createUrlTree']);
    const urlTree = {} as ReturnType<Router['createUrlTree']>;
    router.createUrlTree.and.returnValue(urlTree);
    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: { hasSession: () => false } },
        { provide: Router, useValue: router }
      ]
    });

    const result = TestBed.runInInjectionContext(() => authGuard(
      {} as ActivatedRouteSnapshot,
      { url: '/dashboard' } as RouterStateSnapshot
    ));

    expect(result).toBe(urlTree);
    expect(router.createUrlTree).toHaveBeenCalledWith(['/dashboard/auth/login'], {
      queryParams: { returnUrl: '/dashboard' }
    });
  });

  it('allows a valid session', () => {
    TestBed.configureTestingModule({
      providers: [{ provide: AuthService, useValue: { hasSession: () => true } }]
    });

    const result = TestBed.runInInjectionContext(() => authGuard(
      {} as ActivatedRouteSnapshot,
      { url: '/dashboard' } as RouterStateSnapshot
    ));

    expect(result).toBeTrue();
  });
});
