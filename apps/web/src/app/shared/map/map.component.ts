import { AfterViewInit, Component, ElementRef, EventEmitter, Input, OnChanges, OnDestroy, Output, SimpleChanges, ViewChild } from '@angular/core';
import * as L from 'leaflet';

export type MapPosition = { lat: number; lng: number };
export type MapMarker = MapPosition & { label?: string };

@Component({
  selector: 'app-map',
  standalone: true,
  templateUrl: './map.component.html',
  styleUrl: './map.component.css',
})
export class MapComponent implements AfterViewInit, OnChanges, OnDestroy {
  @Input() mode: 'pin' | 'markers' = 'pin';
  @Input({ required: true }) center!: MapPosition;
  @Input() zoom = 13;
  @Input() initialPosition?: MapPosition;
  @Output() positionChanged = new EventEmitter<MapPosition>();
  @Input() markers: MapMarker[] = [];
  @Output() markerClicked = new EventEmitter<number>();

  @ViewChild('map', { static: true }) private mapElement!: ElementRef<HTMLDivElement>;

  private map?: L.Map;
  private markerLayer = L.layerGroup();

  ngAfterViewInit(): void {
    delete (L.Icon.Default.prototype as L.Icon.Default & { _getIconUrl?: unknown })._getIconUrl;
    L.Icon.Default.mergeOptions({
      iconUrl: 'leaflet/marker-icon.png',
      iconRetinaUrl: 'leaflet/marker-icon-2x.png',
      shadowUrl: 'leaflet/marker-shadow.png',
    });
    this.map = L.map(this.mapElement.nativeElement).setView([this.center.lat, this.center.lng], this.zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(this.map);
    this.markerLayer.addTo(this.map);
    this.renderMarkers();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (this.map && (changes['mode'] || changes['center'] || changes['zoom'] || changes['initialPosition'] || changes['markers'])) {
      this.map.setView([this.center.lat, this.center.lng], this.zoom);
      this.renderMarkers();
    }
  }

  ngOnDestroy(): void {
    this.map?.remove();
    this.map = undefined;
  }

  centerPinToView(): void {
    if (!this.map || this.mode !== 'pin' || !this.pinMarker) return;
    const { lat, lng } = this.map.getCenter();
    this.pinMarker.setLatLng([lat, lng]);
    this.positionChanged.emit({ lat, lng });
  }

  private renderMarkers(): void {
    if (!this.map) return;

    if (this.mode === 'pin') {
      const position = this.initialPosition ?? this.center;
      if (!this.pinMarker) this.pinMarker = L.marker([position.lat, position.lng], { draggable: true })
        .on('dragend', (event) => {
          const { lat, lng } = event.target.getLatLng();
          this.positionChanged.emit({ lat, lng });
        });
      this.pinMarker.setLatLng([position.lat, position.lng]).addTo(this.markerLayer);
      return;
    }

    this.markerLayer.clearLayers();
    this.pinMarker = undefined;
    this.markers.forEach((marker, index) => {
      const leafletMarker = L.marker([marker.lat, marker.lng]);
      if (marker.label) leafletMarker.bindPopup(marker.label);
      leafletMarker.on('click', () => this.markerClicked.emit(index));
      leafletMarker.addTo(this.markerLayer);
    });
  }

  private pinMarker?: L.Marker;
}
