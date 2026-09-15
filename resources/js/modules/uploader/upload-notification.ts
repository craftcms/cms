import {t} from '@craftcms/ui';
import {getSpeed} from '@uppy/core/utils';
import {html, nothing, render} from 'lit';
import type {UploadState} from '@/upload-client';
import {FileUploadNotificationElement} from './file-upload-notification.ce';

declare const Craft: any;

/** Uses the existing CP notification for per-file upload controls. */
export function createFileUploadNotification(
  file: File,
  actions: {
    retry: () => void;
    cancel: () => void;
    pause: () => void;
    resume: () => void;
  }
): {
  updateProgress: (loaded: number) => void;
  updateState: (
    state: UploadState | 'conflict',
    message?: string,
    canPause?: boolean
  ) => void;
  close: () => void;
} {
  const details = new FileUploadNotificationElement();
  let sample: {loaded: number; time: number} | undefined;
  details.total = file.size;
  for (const [action, handler] of Object.entries(actions)) {
    details.addEventListener(`craft-upload-${action}`, handler);
  }

  const close = createUploadNotice(file.name, details);

  return {
    updateProgress(loaded: number) {
      if (details.state === 'uploading') {
        if (!sample || loaded < details.loaded) {
          sample = {loaded, time: Date.now()};
        }
        const speed = getSpeed({
          bytesUploaded: loaded - sample.loaded,
          bytesTotal: file.size,
          uploadStarted: sample.time,
        });
        details.speed = Number.isFinite(speed) && speed > 0 ? speed : 0;
      }
      details.loaded = loaded;
    },
    updateState(
      state: UploadState | 'conflict',
      message = '',
      canPause = false
    ) {
      if (state !== details.state) {
        sample = undefined;
        details.speed = 0;
      }
      details.state = state;
      details.canPause = canPause;
      details.message = message;

      if (state === 'completed' || state === 'canceled') {
        close();
      }
    },
    close,
  };
}

export type UploadConflictChoice = 'keepBoth' | 'replace' | 'cancel';

/** Opening this dialog is an explicit action; dismissing it leaves the conflict pending. */
export function showUploadConflict(
  message: string,
  onChoice: (choice: UploadConflictChoice) => void
): () => void {
  const dialog = document.createElement('craft-dialog');
  dialog.setAttribute('label', t('Filename conflict'));

  const choices: Array<[UploadConflictChoice, string]> = [
    ['cancel', t('Cancel upload')],
    ['keepBoth', t('Keep both')],
    ['replace', t('Replace')],
  ];
  render(
    html`
      <p>${message}</p>
      <div slot="footer" class="flex justify-end gap-2">
        ${choices.map(
          ([choice, label]) => html`
            <craft-button
              variant=${choice === 'replace' ? 'danger' : nothing}
              @click=${() => {
                dialog.remove();
                onChoice(choice);
              }}
              >${label}</craft-button
            >
          `
        )}
      </div>
    `,
    dialog
  );
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
