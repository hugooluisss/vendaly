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

  private renderMarkers(): void {
    if (!this.map) return;
    this.markerLayer.clearLayers();

    if (this.mode === 'pin') {
      const position = this.initialPosition ?? this.center;
      L.marker([position.lat, position.lng], { draggable: true })
        .on('dragend', (event) => {
          const { lat, lng } = event.target.getLatLng();
          this.positionChanged.emit({ lat, lng });
        })
        .addTo(this.markerLayer);
      return;
    }

    this.markers.forEach((marker, index) => {
      const leafletMarker = L.marker([marker.lat, marker.lng]);
      if (marker.label) leafletMarker.bindPopup(marker.label);
      leafletMarker.on('click', () => this.markerClicked.emit(index));
      leafletMarker.addTo(this.markerLayer);
    });
  }
}
