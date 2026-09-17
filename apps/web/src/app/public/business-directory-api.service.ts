import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { map } from 'rxjs';
import { environment } from '../../environments/environment';
import { BusinessDirectoryResponse } from './public.models';

@Injectable({ providedIn: 'root' })
export class BusinessDirectoryApiService {
  private readonly http = inject(HttpClient);

  get(category?: string, location?: string, name?: string, latitude?: number, longitude?: number) {
    let params = new HttpParams();
    if (category) params = params.set('category', category);
    if (location) params = params.set('location', location);
    if (name) params = params.set('name', name);
    if (latitude !== undefined && longitude !== undefined) params = params.set('lat', latitude).set('lng', longitude);
    return this.http.get<BusinessDirectoryResponse>(`${environment.apiBaseUrl}/public/businesses`, { params }).pipe(map(response => response.businesses));
  }
}
