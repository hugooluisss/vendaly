import { Component, inject } from '@angular/core';
import { FormsModule, FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { BusinessApiService } from './business-api.service';
import { ModalComponent } from '../shared/modal.component';
import { ImageUploadComponent } from '../shared/image-upload.component';

const DAYS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
export function unconfiguredDays(hours: { day_of_week: number }[]): number[] { const configured = new Set(hours.map(hour => hour.day_of_week)); return DAYS.map((_, day) => day).filter(day => !configured.has(day)); }

@Component({ standalone: true, imports: [FormsModule, ReactiveFormsModule, ModalComponent, ImageUploadComponent], styleUrl: './business-settings.component.css', template: `<section class="card"><p class="eyebrow">CONFIGURACIÓN</p><h1>Perfil del negocio</h1>@if (businessId) {<button class="button secondary hours-button" (click)="hoursModal = true">Editar horarios</button><form [formGroup]="profile" (ngSubmit)="saveProfile()"><label>Nombre<input formControlName="name"></label><label>WhatsApp<input formControlName="whatsapp_number" placeholder="5215512345678"></label><label>Descripción<textarea formControlName="description"></textarea></label><app-image-upload label="Logo" (cropped)="onLogoSelected($event)"></app-image-upload><button class="button" [disabled]="profile.invalid">Guardar perfil</button></form>} @else {<p>No hay negocio cargado. <a href="/dashboard/onboarding">Créalo primero.</a></p>}</section>@if (hoursModal) {<app-modal title="Horarios" (close)="hoursModal = false"><form (ngSubmit)="saveHours()"><div class="hours">@for (hour of hours; track hour.day_of_week) {<div class="day"><strong>{{ dayNames[hour.day_of_week] }}</strong><label><input type="checkbox" [checked]="hour.is_closed" (change)="hour.is_closed = !hour.is_closed"> Cerrado</label><input type="time" [(ngModel)]="hour.opens_at" [ngModelOptions]="{standalone: true}" [disabled]="hour.is_closed"><input type="time" [(ngModel)]="hour.closes_at" [ngModelOptions]="{standalone: true}" [disabled]="hour.is_closed"></div>}</div><button class="button">Guardar horarios</button></form></app-modal>}` })
export class BusinessSettingsComponent {
  private readonly api = inject(BusinessApiService); businessId = 0; logo?: File; hoursModal = false; hours = Array.from({ length: 7 }, (_, day) => ({ day_of_week: day, opens_at: '09:00', closes_at: '18:00', is_closed: false })); readonly dayNames = DAYS;
  readonly profile = new FormGroup({ name: new FormControl('', { nonNullable: true, validators: [Validators.required] }), whatsapp_number: new FormControl('', { nonNullable: true }), description: new FormControl('', { nonNullable: true }) });
  constructor() { this.api.getMine().subscribe({ next: result => { this.businessId = result.business.id; this.profile.patchValue({ name: result.business.name, whatsapp_number: result.business.whatsapp_number ?? '', description: result.business.description ?? '' }); for (const saved of result.hours) Object.assign(this.hours[saved.day_of_week], saved); }, error: () => this.businessId = 0 }); }
  saveProfile(): void { if (this.profile.invalid || !this.businessId) return; this.api.update(this.businessId, this.profile.getRawValue(), this.logo).subscribe(); }
  onLogoSelected(file: File): void { this.logo = file; }
  saveHours(): void { if (this.businessId) this.api.saveHours(this.businessId, this.hours).subscribe(() => this.hoursModal = false); }
}
