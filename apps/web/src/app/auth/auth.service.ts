import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, map, tap } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthResponse, AuthTokens, readTokens } from './auth.models';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);
  private readonly storageKey = 'vendaly.auth.tokens';
  private readonly apiUrl = environment.apiBaseUrl;

  register(email: string, password: string): Observable<AuthTokens> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/auth/register`, { email, password }).pipe(
      map(readTokens),
      tap(tokens => this.saveTokens(tokens))
    );
  }

  login(email: string, password: string): Observable<AuthTokens> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/auth/login`, { email, password }).pipe(
      map(readTokens),
      tap(tokens => this.saveTokens(tokens))
    );
  }

  refresh(): Observable<AuthTokens> {
    const refreshToken = this.getTokens()?.refreshToken;
    if (!refreshToken) {
      throw new Error('No hay refresh token');
    }

    return this.http.post<AuthResponse>(`${this.apiUrl}/auth/refresh`, { refresh_token: refreshToken }).pipe(
      map(readTokens),
      tap(tokens => this.saveTokens(tokens))
    );
  }

  getAccessToken(): string | null {
    return this.getTokens()?.accessToken ?? null;
  }

  hasSession(): boolean {
    const accessToken = this.getAccessToken();
    if (!accessToken) return false;

    try {
      const payload = JSON.parse(atob(accessToken.split('.')[1]));
      return typeof payload.exp !== 'number' || payload.exp * 1000 > Date.now();
    } catch {
      return true;
    }
  }

  logout(): void {
    localStorage.removeItem(this.storageKey);
    void this.router.navigate(['/dashboard/auth/login']);
  }

  private saveTokens(tokens: AuthTokens): void {
    localStorage.setItem(this.storageKey, JSON.stringify(tokens));
  }

  private getTokens(): AuthTokens | null {
    const raw = localStorage.getItem(this.storageKey);
    if (!raw) return null;

    try {
      return JSON.parse(raw) as AuthTokens;
    } catch {
      this.logout();
      return null;
    }
  }
}
