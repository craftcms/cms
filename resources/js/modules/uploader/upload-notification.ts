import {t} from '@craftcms/ui';
import {router} from '@inertiajs/vue3';
import type {UploadState} from '@/upload-client';

declare const Craft: any;

/** Uses the existing CP notification for per-file retry and cancellation controls. */
export class UploadNotification {
  readonly close: () => void;
  private retryButton = document.createElement('craft-button');
  private cancelButton = document.createElement('craft-button');
  private progress = document.createElement('progress');
  private message = document.createElement('span');

  constructor(file: File, retry: () => void, cancel: () => void) {
    const details = document.createElement('div');
    details.className = 'flex flex-col items-start gap-2';
    const controls = document.createElement('div');
    controls.className = 'flex flex-wrap gap-2';
    this.progress.className = 'w-full';
    this.message.className = 'wrap-anywhere';
    this.message.hidden = true;
    this.progress.max = file.size;
    this.progress.value = 0;
    this.progress.setAttribute('aria-label', t('Upload progress'));
    this.retryButton.textContent = t('Retry');
    this.retryButton.hidden = true;
    this.retryButton.addEventListener('click', retry);
    this.cancelButton.textContent = t('Cancel');
    this.cancelButton.addEventListener('click', cancel);
    controls.append(this.retryButton, this.cancelButton);
    details.append(this.progress, this.message, controls);
    this.close = createUploadNotice(file.name, details);
  }

  updateProgress(loaded: number): void {
    this.progress.value = loaded;
  }

  updateState(state: UploadState | 'conflict', message = ''): void {
    this.message.textContent = message;
    this.message.hidden = !message;
    this.progress.hidden = state === 'failed' || state === 'conflict';
    this.retryButton.textContent =
      state === 'conflict' ? t('Action required') : t('Retry');
    this.retryButton.hidden = state !== 'failed' && state !== 'conflict';
    this.cancelButton.toggleAttribute('disabled', state === 'completing');

    if (state === 'completed' || state === 'canceled') {
      this.close();
    }
  }
}

export type UploadConflictChoice = 'keepBoth' | 'replace' | 'cancel';

export interface AssetUploadDestination {
  folderId: number;
  url: string;
  label: string;
}

/** One dismissible notification for the successful files in a selection. */
export class UploadBatchNotification {
  private count = document.createElement('span');
  readonly close: () => void;

  constructor(destination: AssetUploadDestination, onClose: () => void) {
    const details = document.createElement('div');
    const link = document.createElement('a');
    details.className = 'flex flex-wrap items-center gap-2';
    link.className = 'wrap-anywhere';
    link.href = destination.url;
    link.textContent = destination.label;
    link.addEventListener('click', (event) => {
      if (
        event.button ||
        event.ctrlKey ||
        event.metaKey ||
        event.shiftKey ||
        event.altKey
      ) {
        return;
      }
      event.preventDefault();
      router.visit(destination.url);
    });
    details.append(this.count, link);
    this.close = createUploadNotice(t('Upload complete.'), details, onClose);
  }

  update(count: number): void {
    this.count.textContent = t(
      '{num, plural, =1{# file uploaded.} other{# files uploaded.}}',
      {num: count}
    );
  }
}

/** Opening this dialog is an explicit action; dismissing it leaves the conflict pending. */
export function showUploadConflict(
  message: string,
  onChoice: (choice: UploadConflictChoice) => void
): () => void {
  const dialog = document.createElement('craft-dialog');
  dialog.setAttribute('label', t('Filename conflict'));
  const body = document.createElement('p');
  body.textContent = message;
  const footer = document.createElement('div');
  footer.slot = 'footer';
  footer.className = 'flex justify-end gap-2';

  const choices: Array<[UploadConflictChoice, string]> = [
    ['cancel', t('Cancel upload')],
    ['keepBoth', t('Keep both')],
    ['replace', t('Replace')],
  ];
  for (const [choice, label] of choices) {
    const button = document.createElement('craft-button');
    button.textContent = label;
    if (choice === 'replace') {
      button.setAttribute('variant', 'danger');
    }
    button.addEventListener('click', () => {
      dialog.remove();
      onChoice(choice);
    });
    footer.append(button);
  }

  dialog.append(body, footer);
  dialog.addEventListener('craft-after-hide', () => dialog.remove());
  document.body.append(dialog);
  dialog.setAttribute('open', '');

  return () => dialog.remove();
}

/** CP notices appear asynchronously, so a close request may precede the show event. */
function createUploadNotice(
  message: string,
  details: HTMLElement,
  onClose?: () => void
): () => void {
  let shown = false;
  let finished = false;
  const notification = Craft.cp.displayNotice(message, {
    details,
    persist: true,
  });
  notification.on('show', () => {
    shown = true;
    if (onClose) {
      notification.$closeBtn.on('click', onClose);
    } else {
      notification.$closeBtn.hide();
    }
    if (finished) {
      notification.close();
    }
  });

  return () => {
    finished = true;
    onClose?.();
    if (shown) {
      notification.close();
    }
  };
}
