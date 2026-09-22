import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class CustomerPhoneService {
  load(): string { try { return localStorage.getItem('vendaly.customer-phone') ?? ''; } catch { return ''; } }
  persist(phone: string): void { try { localStorage.setItem('vendaly.customer-phone', phone); } catch {} }
}
