import {t} from '@craftcms/ui';
import {html, LitElement} from 'lit';
import {customElement, property} from 'lit/decorators.js';

/** @fires craft-upload-navigate - Open the upload destination in the current page. */
@customElement('craft-upload-complete-notification')
export class UploadCompleteNotificationElement extends LitElement {
  @property({type: Number})
  count = 0;

  @property()
  url = '';

  @property()
  label = '';

  protected override createRenderRoot(): HTMLElement {
    return this;
  }

  private navigate(event: MouseEvent): void {
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
    this.dispatchEvent(
      new CustomEvent('craft-upload-navigate', {bubbles: true, composed: true})
    );
  }

  protected override render() {
    return html`
      <div class="flex flex-wrap items-center gap-2">
        <span
          >${t('{num, plural, =1{# file uploaded.} other{# files uploaded.}}', {
            num: this.count,
          })}</span
        >
        <a
          class="wrap-anywhere"
          href=${this.url}
          @click=${(event: MouseEvent) => this.navigate(event)}
          >${this.label}</a
        >
      </div>
    `;
  }
}
