import { Component, EventEmitter, HostListener, Input, Output } from '@angular/core';

@Component({
  selector: 'app-modal',
  standalone: true,
  templateUrl: './modal.component.html',
  styleUrl: './modal.component.css',
})
export class ModalComponent {
  @Input() title = '';
  @Input() size: 'md' | 'lg' = 'md';
  @Output() close = new EventEmitter<void>();

  @HostListener('document:keydown.escape') onEscape(): void { this.close.emit(); }
  onOverlayClick(event: MouseEvent): void { if (event.target === event.currentTarget) this.close.emit(); }
}
