import {t} from '@craftcms/ui';
import {router} from '@inertiajs/vue3';
import {getSpeed} from '@uppy/core/utils';
import {html, render} from 'lit';
import type {UploadState} from '@/upload-client';
import type {ConflictResolution} from '@/modules/assets/assetMover';
import {AssetConflictResolutionElement} from '@/modules/assets/asset-conflict-resolution.ce';
import {FileUploadNotificationElement} from './file-upload-notification.ce';
import {UploadCompleteNotificationElement} from './upload-complete-notification.ce';

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
    state: UploadState,
    message?: string,
    canPause?: boolean
  ) => void;
  /** Replaces the transfer controls with `content` until the next `updateState()`. */
  showDetails: (content: HTMLElement) => void;
  close: () => void;
} {
  const details = new FileUploadNotificationElement();
  let sample: {loaded: number; time: number} | undefined;
  details.total = file.size;
  for (const [action, handler] of Object.entries(actions)) {
    details.addEventListener(`craft-upload-${action}`, handler);
  }

  const container = document.createElement('div');
  container.tabIndex = -1;
  container.append(details);
  const swap = (content: HTMLElement) => {
    if (container.firstElementChild === content) {
      return;
    }
    const hadFocus = container.contains(document.activeElement);
    container.replaceChildren(content);
    if (hadFocus) {
      container.focus();
    }
  };

  const close = createUploadNotice(file.name, container);

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
    updateState(state: UploadState, message = '', canPause = false) {
      swap(details);
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
    showDetails: swap,
    close,
  };
}

export interface AssetUploadDestination {
  folderId: number;
  url: string;
  label: string;
}

/** One dismissible notification for the successful files in a selection. */
export function createUploadCompleteNotification(
  destination: AssetUploadDestination,
  onClose: () => void
): {update: (count: number) => void; close: () => void} {
  const details = new UploadCompleteNotificationElement();
  details.url = destination.url;
  details.label = destination.label;
  details.addEventListener('craft-upload-navigate', () =>
    router.visit(destination.url)
  );

  const close = createUploadNotice(t('Upload complete.'), details, onClose);

  return {
    update(count: number) {
      details.count = count;
    },
    close,
  };
}

/** A filename conflict's message and choices, for a transfer notification's details. */
export function createUploadConflictDetails(
  message: string,
  onChoice: (choice: ConflictResolution) => void
): HTMLElement {
  const details = document.createElement('div');
  details.className = 'flex flex-col items-start gap-2';
  const choices = new AssetConflictResolutionElement();
  choices.className = 'flex flex-wrap gap-2';
  choices.addEventListener('craft-conflict-resolve', (event) =>
    onChoice((event as CustomEvent<ConflictResolution>).detail)
  );
  render(html`<span class="wrap-anywhere">${message}</span>`, details);
  details.append(choices);
  return details;
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
