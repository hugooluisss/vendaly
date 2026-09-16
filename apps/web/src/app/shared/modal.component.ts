import { Component, EventEmitter, HostListener, Input, Output } from '@angular/core';

@Component({
  selector: 'app-modal',
  standalone: true,
  template: `<div class="overlay" (click)="onOverlayClick($event)">
    <section class="modal" role="dialog" aria-modal="true" [attr.aria-label]="title">
      <header><h2>{{ title }}</h2><button type="button" class="close" aria-label="Cerrar" (click)="close.emit()">×</button></header>
      <ng-content></ng-content>
    </section>
  </div>`,
  styles: [`
    .overlay { position: fixed; inset: 0; z-index: 1000; display: grid; place-items: center; padding: 1rem; background: color-mix(in srgb, var(--vendaly-slate) 60%, transparent); }
    .modal { width: min(100%, 42rem); max-height: calc(100vh - 2rem); overflow: auto; padding: 1.5rem; background: var(--vendaly-surface); border: 1px solid var(--vendaly-border); border-radius: 1rem; box-shadow: 0 12px 40px var(--vendaly-shadow); }
    header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    h2 { margin: 0; letter-spacing: -.03em; }
    .close { border: 0; background: transparent; color: var(--vendaly-muted); font-size: 1.75rem; line-height: 1; cursor: pointer; }
    .close:hover { color: var(--vendaly-orange); }
  `]
})
export class ModalComponent {
  @Input() title = '';
  @Output() close = new EventEmitter<void>();

  @HostListener('document:keydown.escape') onEscape(): void { this.close.emit(); }
  onOverlayClick(event: MouseEvent): void { if (event.target === event.currentTarget) this.close.emit(); }
}
