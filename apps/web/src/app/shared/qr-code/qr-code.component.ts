import { AfterViewInit, Component, ElementRef, Input, OnChanges, ViewChild } from '@angular/core';
import * as QRCode from 'qrcode';

@Component({
  selector: 'app-qr-code',
  standalone: true,
  templateUrl: './qr-code.component.html',
  styleUrl: './qr-code.component.css',
})
export class QrCodeComponent implements AfterViewInit, OnChanges {
  @Input() value: string | undefined;
  @ViewChild('canvas') private canvas?: ElementRef<HTMLCanvasElement>;
  qrReady = false;
  private renderId = 0;

  ngAfterViewInit(): void { this.render(); }
  ngOnChanges(): void { this.render(); }

  download(filename: string): void {
    if (!this.qrReady || !this.canvas) return;
    const link = document.createElement('a');
    link.href = this.canvas.nativeElement.toDataURL('image/png');
    link.download = filename;
    link.click();
  }

  private render(): void {
    const renderId = ++this.renderId;
    this.qrReady = false;
    if (!this.canvas || !this.value) return;
    const canvas = this.canvas.nativeElement;
    void QRCode.toCanvas(canvas, this.value)
      .then(() => { if (renderId === this.renderId) this.qrReady = true; })
      .catch(() => { if (renderId === this.renderId) this.qrReady = false; });
  }
}
