import { Component, inject } from '@angular/core';
import { FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { BusinessApiService } from '../business-api.service';

@Component({ standalone: true, imports: [ReactiveFormsModule], templateUrl: './onboarding.component.html', styleUrl: './onboarding.component.css' })
export class OnboardingComponent {
  private readonly api = inject(BusinessApiService); private readonly router = inject(Router);
  readonly name = new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.maxLength(255)] }); created = false;
  create(): void { if (this.name.invalid) return; this.api.create(this.name.value).subscribe(() => this.created = true); }
  goSettings(): void { void this.router.navigate(['/dashboard/settings']); }
}
