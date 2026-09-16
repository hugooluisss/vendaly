import { Component, EventEmitter, Input, OnDestroy, Output } from '@angular/core';
import { ImageCroppedEvent, ImageCropperComponent } from 'ngx-image-cropper';
import { ModalComponent } from './modal.component';

@Component({
  selector: 'app-image-upload',
  standalone: true,
  imports: [ImageCropperComponent, ModalComponent],
  template: `<label>{{ label }}<input type="file" accept="image/*" (change)="selectFile($event)"></label>
    @if (previewUrl) { <img class="preview" [src]="previewUrl" [alt]="label + ' seleccionada'"> }
    @if (editorOpen) {
      <app-modal title="Ajustar imagen" (close)="cancel()">
        <image-cropper
          [imageChangedEvent]="imageChangedEvent"
          [maintainAspectRatio]="true"
          [aspectRatio]="aspectRatio"
          [format]="'png'"
          [output]="'blob'"
          [resizeToWidth]="1200"
          (imageCropped)="imageCropped($event)"
          (loadImageFailed)="loadImageFailed()">
        </image-cropper>
        @if (error) { <p class="error">{{ error }}</p> }
        <div class="actions">
          <button type="button" class="button secondary" (click)="cancel()">Cancelar</button>
          <button type="button" class="button" [disabled]="!croppedFile" (click)="confirm()">Usar imagen</button>
        </div>
      </app-modal>
    }`,
  styles: [`
    label { display: grid; gap: .4rem; margin: .7rem 0; }
    input { width: 100%; padding: .7rem; border: 1px solid var(--vendaly-border-strong); border-radius: .6rem; font: inherit; }
    .preview { display: block; width: 7rem; height: 7rem; margin-top: .75rem; object-fit: cover; border-radius: .7rem; border: 1px solid var(--vendaly-border); }
    image-cropper { display: block; max-height: 60vh; }
    .actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1rem; }
    .button { padding: .7rem 1rem; border: 0; border-radius: .65rem; background: var(--vendaly-orange); color: white; font-weight: 700; cursor: pointer; }
    .button.secondary { background: var(--vendaly-slate); }
    .button:disabled { opacity: .6; cursor: not-allowed; }
    .error { color: var(--vendaly-danger); }
  `]
})
export class ImageUploadComponent implements OnDestroy {
  @Input() label = 'Imagen';
  @Input() aspectRatio = 1;
  @Output() cropped = new EventEmitter<File>();

  imageChangedEvent: Event | null = null;
  croppedFile?: File;
  previewUrl?: string;
  editorOpen = false;
  error = '';

  selectFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file || !file.type.startsWith('image/')) return;
    this.croppedFile = undefined;
    this.error = '';
    this.imageChangedEvent = event;
    this.editorOpen = true;
  }

  imageCropped(event: ImageCroppedEvent): void {
    if (!event.blob) return;
    this.croppedFile = new File([event.blob], `${this.baseName()}.png`, { type: 'image/png' });
  }

  confirm(): void {
    if (!this.croppedFile) return;
    this.setPreview(this.croppedFile);
    this.cropped.emit(this.croppedFile);
    this.editorOpen = false;
  }

  cancel(): void {
    this.editorOpen = false;
    this.imageChangedEvent = null;
    this.croppedFile = undefined;
  }

  loadImageFailed(): void { this.error = 'No se pudo cargar esa imagen.'; }

  ngOnDestroy(): void { if (this.previewUrl) URL.revokeObjectURL(this.previewUrl); }

  private baseName(): string {
    const file = (this.imageChangedEvent as InputEvent | null)?.target as HTMLInputElement | null;
    return file?.files?.[0]?.name.replace(/\.[^.]+$/, '') || 'imagen';
  }

  private setPreview(file: File): void {
    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
    this.previewUrl = URL.createObjectURL(file);
  }
}
