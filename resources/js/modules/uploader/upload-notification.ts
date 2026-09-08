import {t} from '@craftcms/ui';
import type {UploadState} from '@/upload-client';

declare const Craft: any;

/** Uses the existing CP notification for per-file retry and cancellation controls. */
export class UploadNotification {
  private notification: any;
  private shown = false;
  private finished = false;
  private retryButton = document.createElement('craft-button');
  private cancelButton = document.createElement('craft-button');
  private progress = document.createElement('progress');

  constructor(file: File, retry: () => void, cancel: () => void) {
    const details = document.createElement('div');
    details.className = 'flex items-center gap-2';
    this.progress.max = file.size;
    this.progress.value = 0;
    this.progress.setAttribute('aria-label', t('Upload progress'));
    this.retryButton.textContent = t('Retry');
    this.retryButton.hidden = true;
    this.retryButton.addEventListener('click', retry);
    this.cancelButton.textContent = t('Cancel');
    this.cancelButton.addEventListener('click', cancel);
    details.append(this.progress, this.retryButton, this.cancelButton);
    this.notification = Craft.cp.displayNotice(file.name, {
      details,
      persist: true,
    });
    this.notification.on('show', () => {
      this.notification.$closeBtn.hide();
      this.shown = true;
      if (this.finished) {
        this.notification.close();
      }
    });
  }

  updateProgress(loaded: number): void {
    this.progress.value = loaded;
  }

  updateState(state: UploadState): void {
    this.retryButton.hidden = state !== 'failed';
    this.cancelButton.toggleAttribute('disabled', state === 'completing');

    if (state === 'completed' || state === 'canceled') {
      this.close();
    }
  }

  close(): void {
    this.finished = true;
    if (this.shown) {
      this.notification.close();
    }
  }
}
