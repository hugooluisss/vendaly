import { fakeAsync, flushMicrotasks, TestBed } from '@angular/core/testing';
import { QrCodeComponent } from './qr-code.component';

describe('QrCodeComponent', () => {
  it('does not render or download without a value', () => {
    const fixture = TestBed.createComponent(QrCodeComponent);
    const click = spyOn(HTMLAnchorElement.prototype, 'click').and.stub();
    fixture.detectChanges();
    const canvas = fixture.nativeElement.querySelector('canvas') as HTMLCanvasElement;
    expect(canvas.hidden).toBeTrue();
    expect(canvas.width).toBe(300);
    expect(fixture.componentInstance.qrReady).toBeFalse();
    fixture.componentInstance.download('catalogo-qr.png');
    expect(click).not.toHaveBeenCalled();
  });

  it('renders a valid value and downloads only after it is ready', fakeAsync(() => {
    const fixture = TestBed.createComponent(QrCodeComponent);
    const click = spyOn(HTMLAnchorElement.prototype, 'click').and.stub();
    fixture.componentRef.setInput('value', 'https://example.com/catalogo');
    fixture.detectChanges();
    fixture.componentInstance.download('catalogo-qr.png');
    expect(click).not.toHaveBeenCalled();
    flushMicrotasks();
    expect(fixture.componentInstance.qrReady).toBeTrue();
    const canvas = fixture.nativeElement.querySelector('canvas') as HTMLCanvasElement;
    expect(canvas.toDataURL('image/png')).toContain('data:image/png;base64,');
    fixture.componentInstance.download('catalogo-qr.png');
    expect(click).toHaveBeenCalledTimes(1);
    expect((click.calls.mostRecent().object as HTMLAnchorElement).download).toBe('catalogo-qr.png');
  }));
});
