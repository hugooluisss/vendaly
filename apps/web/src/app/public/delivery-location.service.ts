import { Injectable } from '@angular/core';
import { MapPosition } from '../shared/map/map.component';

export interface DeliveryLocation { address: string; position?: MapPosition; }

@Injectable({ providedIn: 'root' })
export class DeliveryLocationService {
  load(): DeliveryLocation { try { const value = JSON.parse(localStorage.getItem('vendaly.delivery-location') ?? 'null'); return value && typeof value.address === 'string' ? value : { address: '' }; } catch { return { address: '' }; } }
  persist(location: DeliveryLocation): void { try { localStorage.setItem('vendaly.delivery-location', JSON.stringify(location)); } catch {} }
}
