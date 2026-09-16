import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../auth/auth.service';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  styleUrl: './auth.component.css',
  template: `
    <main>
      <h1>{{ registerMode ? 'Crear cuenta' : 'Iniciar sesión' }}</h1>
      <form [formGroup]="form" (ngSubmit)="submit()">
        <label>Email <input type="email" formControlName="email" autocomplete="email"></label>
        <label>Contraseña <input type="password" formControlName="password" autocomplete="current-password"></label>
        @if (error) { <p role="alert">{{ error }}</p> }
        <button type="submit" [disabled]="form.invalid || submitting">{{ registerMode ? 'Registrarme' : 'Entrar' }}</button>
      </form>
      <a [routerLink]="registerMode ? '../login' : '../register'">
        {{ registerMode ? 'Ya tengo una cuenta' : 'Crear una cuenta' }}
      </a>
    </main>
  `
})
export class AuthComponent {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly fb = inject(FormBuilder);
  readonly registerMode = this.route.snapshot.data['mode'] === 'register';
  readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]]
  });
  submitting = false;
  error = '';

  submit(): void {
    if (this.form.invalid) return;
    this.submitting = true;
    this.error = '';
    const { email, password } = this.form.getRawValue();
    const request = this.registerMode ? this.auth.register(email, password) : this.auth.login(email, password);
    request.subscribe({
      next: () => void this.router.navigate(['/dashboard']),
      error: () => {
        this.error = 'No se pudo completar la autenticación.';
        this.submitting = false;
      }
    });
  }
}
