import { Component, inject } from '@angular/core';
import { FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { BusinessApiService } from './business-api.service';

@Component({ standalone: true, imports: [ReactiveFormsModule], template: `<section class="card"><h1>{{ created ? 'Negocio creado' : 'Crea tu negocio' }}</h1>@if (!created) {<p>Solo necesitamos el nombre. El slug público lo genera el servidor.</p><form (ngSubmit)="create()"><label>Nombre<input [formControl]="name" autofocus></label><button class="button" [disabled]="name.invalid">Continuar</button></form>} @else {<p>Ya puedes completar tu perfil y horarios.</p><button class="button" (click)="goSettings()">Configurar negocio</button>}</section>` })
export class OnboardingComponent {
  private readonly api = inject(BusinessApiService); private readonly router = inject(Router);
  readonly name = new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.maxLength(255)] }); created = false;
  create(): void { if (this.name.invalid) return; this.api.create(this.name.value).subscribe(() => this.created = true); }
  goSettings(): void { void this.router.navigate(['/dashboard/settings']); }
}
