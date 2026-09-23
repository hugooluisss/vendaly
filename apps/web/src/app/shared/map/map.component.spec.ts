import { TestBed } from '@angular/core/testing';
import * as L from 'leaflet';
import { MapComponent } from './map.component';

describe('MapComponent', () => {
  it('uses the configured marker URL without Leaflet auto-detecting a prefix', () => {
    const detectIconPath = spyOn(L.Icon.Default.prototype as L.Icon.Default & { _detectIconPath: () => string }, '_detectIconPath').and.returnValue('/hashed/leaflet.css/');
    const fixture = TestBed.createComponent(MapComponent);
    fixture.componentRef.setInput('center', { lat: 19.4326, lng: -99.1332 });

    fixture.detectChanges();

    const icon = new L.Icon.Default();
    expect(icon.createIcon().getAttribute('src')).toBe('leaflet/marker-icon.png');
    expect(detectIconPath).not.toHaveBeenCalled();

    fixture.destroy();
  });
});
