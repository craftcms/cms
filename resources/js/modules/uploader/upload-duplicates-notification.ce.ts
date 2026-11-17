import {t} from '@craftcms/ui';
import {html, LitElement, nothing} from 'lit';
import {customElement, property} from 'lit/decorators.js';
import {styleMap} from 'lit/directives/style-map.js';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';

export interface UploadDuplicate {
  id: number;
  title: string;
  url: string;
  /** The image's chip, linked to its edit page, as the server renders it. */
  chipHtml: string;
}

/** How many images are drawn behind the closest one, at most. */
const STACK_DEPTH = 4;
/** How far each image behind is offset from the one in front of it, in pixels. */
const STACK_OFFSET = 4;

/**
 * The images an upload looks like a copy of, as chips. The closest one is in
 * front, and any others are stacked faded behind it, like copied elements.
 *
 * @fires craft-upload-navigate - Open a duplicate's edit page in the current page, with its URL as the detail. Cancel
 * the event to handle it; otherwise the link is followed. Not fired for links that open in a new window.
 */
@customElement('craft-upload-duplicates-notification')
export class UploadDuplicatesNotificationElement extends LitElement {
  @property({attribute: false})
  duplicates: UploadDuplicate[] = [];

  /**
   * Whether the images open in a new window, for notices that wouldn't
   * survive following a link, so the uploader doesn't lose them or the page.
   */
  @property({type: Boolean, attribute: 'new-window'})
  newWindow = false;

  protected override createRenderRoot(): HTMLElement {
    return this;
  }

  private navigate(event: MouseEvent): void {
    const link = (event.target as Element | null)?.closest<HTMLAnchorElement>(
      'a[href]'
    );

    if (
      !link ||
      link.target === '_blank' ||
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
        detail: link.getAttribute('href'),
      })
    );

    if (handled) {
      event.preventDefault();
    }
  }

  protected override updated(): void {
    if (!this.newWindow) {
      return;
    }

    for (const link of this.querySelectorAll<HTMLAnchorElement>(
      '.cp-chip-stack__chip:first-child a[href]:not([target])'
    )) {
      link.target = '_blank';
      link.rel = 'noopener';
      const icon = document.createElement('craft-icon');
      icon.setAttribute('name', 'arrow-up-right-from-square');
      icon.className = 'ms-sm';
      const label = document.createElement('span');
      label.className = 'sr-only';
      label.textContent = t('Opens in a new window');
      link.append(icon, label);
    }
  }

  protected override render() {
    const stack = this.duplicates.slice(0, STACK_DEPTH + 1);

    if (!stack.length) {
      return nothing;
    }

    const depth = stack.length - 1;

    return html`
      <div
        class="cp-chip-stack"
        style=${styleMap({
          paddingBlockEnd: `${depth * STACK_OFFSET}px`,
          paddingInlineEnd: `${depth * STACK_OFFSET}px`,
        })}
        @click=${(event: MouseEvent) => this.navigate(event)}
      >
        ${stack.map(
          (duplicate, index) => html`
            <div
              class="cp-chip-stack__chip"
              style=${styleMap(
                index
                  ? {
                      zIndex: `${depth - index + 1}`,
                      insetBlockStart: `${index * STACK_OFFSET}px`,
                      insetInlineStart: `${index * STACK_OFFSET}px`,
                      '--overlay-opacity': `${Math.min(0.25 + 0.15 * index, 1)}`,
                    }
                  : {zIndex: `${depth + 1}`}
              )}
              aria-hidden=${index ? 'true' : nothing}
              ?inert=${index > 0}
            >
              ${unsafeHTML(duplicate.chipHtml)}
            </div>
          `
        )}
      </div>
    `;
  }
}
