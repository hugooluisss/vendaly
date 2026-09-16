import { HttpContextToken, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, switchMap, throwError } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';

const retried = new HttpContextToken(() => false);

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const auth = inject(AuthService);
  const router = inject(Router);
  const isApiRequest = request.url.startsWith(environment.apiBaseUrl);
  const isRefresh = request.url.endsWith('/auth/refresh');
  const accessToken = auth.getAccessToken();
  const authorizedRequest = isApiRequest && accessToken && !isRefresh
    ? request.clone({ setHeaders: { Authorization: `Bearer ${accessToken}` } })
    : request;

  return next(authorizedRequest).pipe(
    catchError(error => {
      if (error.status !== 401 || !isApiRequest || isRefresh || request.context.get(retried)) {
        return throwError(() => error);
      }

      return auth.refresh().pipe(
        switchMap(tokens => next(request.clone({
          setHeaders: { Authorization: `Bearer ${tokens.accessToken}` },
          context: request.context.set(retried, true)
        }))),
        catchError(refreshError => {
          auth.logout();
          return throwError(() => refreshError);
        })
      );
    })
  );
};
