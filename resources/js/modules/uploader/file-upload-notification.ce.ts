import {t} from '@craftcms/ui';
import {prettyETA} from '@uppy/core/utils';
import {html, LitElement, nothing} from 'lit';
import {customElement, property} from 'lit/decorators.js';
import type {UploadState} from '@/upload-client';

/**
 * @fires craft-upload-retry - Retry the upload or resolve its filename conflict.
 * @fires craft-upload-cancel - Cancel the upload.
 * @fires craft-upload-pause - Pause the active transfer.
 * @fires craft-upload-resume - Resume the paused transfer.
 */
@customElement('craft-file-upload-notification')
export class FileUploadNotificationElement extends LitElement {
  @property({type: Number})
  loaded = 0;

  @property({type: Number})
  total = 0;

  /** Current transfer speed in bytes per second. */
  @property({type: Number})
  speed = 0;

  @property()
  state: UploadState | 'conflict' = 'ready';

  @property()
  message = '';

  @property({type: Boolean})
  canPause = false;

  protected override createRenderRoot(): HTMLElement {
    return this;
  }

  private requestAction(action: 'retry' | 'cancel' | 'pause' | 'resume'): void {
    this.dispatchEvent(
      new CustomEvent(`craft-upload-${action}`, {bubbles: true, composed: true})
    );
  }

  protected override render() {
    const needsAction = this.state === 'failed' || this.state === 'conflict';
    const paused = this.state === 'paused';
    const message = paused
      ? t('Upload paused.')
      : this.state === 'completing'
        ? t('Saving file…')
        : this.message;
    const showEstimate =
      this.state === 'uploading' && this.speed > 0 && this.loaded < this.total;

    return html`
      <div class="flex flex-col items-start gap-2">
        <progress
          class="w-full"
          .max=${this.total}
          .value=${this.loaded}
          aria-label=${t('Upload progress')}
          ?hidden=${needsAction}
        ></progress>
        <span class="wrap-anywhere" ?hidden=${needsAction}>
          ${t('{loaded} of {total}', {
            loaded: formatBytes(this.loaded),
            total: formatBytes(this.total),
          })}${showEstimate
            ? html` · ${t('{speed}/s', {speed: formatBytes(this.speed)})} ·
              ${t('{time} left', {
                time: prettyETA(
                  Math.ceil((this.total - this.loaded) / this.speed)
                ),
              })}`
            : nothing}
        </span>
        <span class="wrap-anywhere" ?hidden=${!message}>${message}</span>
        <div class="flex flex-wrap gap-2">
          <craft-button
            ?hidden=${!this.canPause && !paused}
            @click=${() => this.requestAction(paused ? 'resume' : 'pause')}
            >${paused ? t('Resume') : t('Pause')}</craft-button
          >
          <craft-button
            ?hidden=${!needsAction}
            @click=${() => this.requestAction('retry')}
            >${this.state === 'conflict'
              ? t('Action required')
              : t('Retry')}</craft-button
          >
          <craft-button
            ?disabled=${this.state === 'completing'}
            @click=${() => this.requestAction('cancel')}
            >${t('Cancel')}</craft-button
          >
        </div>
      </div>
    `;
  }
}

function formatBytes(bytes: number): string {
  const units = ['byte', 'kilobyte', 'megabyte', 'gigabyte', 'terabyte'];
  const exponent = Math.min(
    Math.floor(Math.log10(Math.max(bytes, 1)) / 3),
    units.length - 1
  );
  return new Intl.NumberFormat(document.documentElement.lang || undefined, {
    style: 'unit',
    unit: units[exponent],
    unitDisplay: 'short',
    maximumFractionDigits: 1,
  }).format(bytes / 1000 ** exponent);
}
