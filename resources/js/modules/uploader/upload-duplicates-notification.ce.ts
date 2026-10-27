import {html, LitElement} from 'lit';
import {customElement, property} from 'lit/decorators.js';

export interface UploadDuplicate {
  id: number;
  title: string;
  url: string;
}

/**
 * @fires craft-upload-navigate - Open a duplicate's edit page in the current page, with its URL as the detail. Cancel
 * the event to handle it; otherwise the link is followed.
 */
@customElement('craft-upload-duplicates-notification')
export class UploadDuplicatesNotificationElement extends LitElement {
  @property({attribute: false})
  duplicates: UploadDuplicate[] = [];

  protected override createRenderRoot(): HTMLElement {
    return this;
  }

  private navigate(event: MouseEvent, url: string): void {
    if (
      event.button ||
      event.ctrlKey ||
      event.metaKey ||
      event.shiftKey ||
      event.altKey
    ) {
      return;
    }

    const handled = !this.dispatchEvent(
      new CustomEvent('craft-upload-navigate', {
        bubbles: true,
        composed: true,
        cancelable: true,
        detail: url,
      })
    );

    if (handled) {
      event.preventDefault();
    }
  }

  protected override render() {
    return html`
      <ul class="list-disc ps-5">
        ${this.duplicates.map(
          (duplicate) => html`
            <li>
              <a
                class="wrap-anywhere"
                href=${duplicate.url}
                @click=${(event: MouseEvent) =>
                  this.navigate(event, duplicate.url)}
                >${duplicate.title}</a
              >
            </li>
          `
        )}
      </ul>
    `;
  }
}
