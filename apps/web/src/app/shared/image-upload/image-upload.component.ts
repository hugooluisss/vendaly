import { Component, EventEmitter, Input, OnDestroy, Output } from '@angular/core';
import { ImageCroppedEvent, ImageCropperComponent } from 'ngx-image-cropper';
import { ModalComponent } from '../modal/modal.component';

@Component({
  selector: 'app-image-upload',
  standalone: true,
  imports: [ImageCropperComponent, ModalComponent],
  templateUrl: './image-upload.component.html',
  styleUrl: './image-upload.component.css',
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
