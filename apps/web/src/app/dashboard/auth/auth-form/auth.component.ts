import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../auth/auth.service';
import { NotificationService } from '../../../shared/notification.service';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  styleUrl: './auth.component.css',
  templateUrl: './auth.component.html'
})
export class AuthComponent {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly fb = inject(FormBuilder);
  readonly notification = inject(NotificationService);
  readonly registerMode = this.route.snapshot.data['mode'] === 'register';
  readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]]
  });
  submitting = false;

  submit(): void {
    if (this.form.invalid) return;
    this.submitting = true;
    const { email, password } = this.form.getRawValue();
    const request = this.registerMode ? this.auth.register(email, password) : this.auth.login(email, password);
    request.subscribe({
      next: () => void this.router.navigate(['/dashboard']),
      error: () => {
        this.notification.error('No se pudo completar la autenticación.');
        this.submitting = false;
      }
    });
  }
}
