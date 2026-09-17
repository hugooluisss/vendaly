import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map } from 'rxjs';
import { environment } from '../../environments/environment';
import { Business, BusinessHour, BusinessSettings } from './business.models';

@Injectable({ providedIn: 'root' })
export class BusinessApiService {
  private readonly http = inject(HttpClient);
  private readonly url = `${environment.apiBaseUrl}/businesses`;

  create(name: string) { return this.http.post<{ business: Business }>(this.url, { name }).pipe(map(r => r.business)); }
  getMine() { return this.http.get<BusinessSettings>(`${this.url}/me`); }
  update(id: number, fields: Partial<Pick<Business, 'name' | 'whatsapp_number' | 'description' | 'category' | 'location' | 'latitude' | 'longitude'>>, logo?: File, coverImage?: File) {
    if (!logo && !coverImage) return this.http.patch<{ business: Business }>(`${this.url}/${id}`, fields).pipe(map(r => r.business));
    const body = new FormData();
    Object.entries(fields).forEach(([key, value]) => body.append(key, String(value ?? '')));
    if (logo) body.append('logo', logo, logo.name);
    if (coverImage) body.append('cover_image', coverImage, coverImage.name);
    return this.http.post<{ business: Business }>(`${this.url}/${id}`, body).pipe(map(r => r.business));
  }
  saveHours(id: number, hours: BusinessHour[]) {
    return this.http.put<{ hours: BusinessHour[] }>(`${this.url}/${id}/hours`, { days: hours }).pipe(map(r => r.hours));
  }
  publish(id: number, published: boolean) {
    return this.http.post<{ business: Business }>(`${this.url}/${id}/publish`, { published }).pipe(map(r => r.business));
  }
}
