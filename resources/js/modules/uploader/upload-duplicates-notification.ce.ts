import {t} from '@craftcms/ui';
import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {customElement, property} from 'lit/decorators.js';
import {fadeOverflow} from '@/modules/messages/copied-notification';

export type UploadDuplicate = NonNullable<
  CraftCms.Cms.Asset.Data.UploadResult['duplicates']
>[number];

/**
 * The images an upload looks like a copy of, as small rounded chips side by
 * side, as "copied" cards show copied elements. Past about three rows they
 * fade out, and the chips out of sight can't be reached.
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

  private chips: HTMLElement[] = [];
  private stopFading?: () => void;

  protected override willUpdate(changed: PropertyValues<this>): void {
    if (changed.has('duplicates') || changed.has('newWindow')) {
      this.chips = this.duplicates.flatMap((duplicate) => {
        const template = document.createElement('template');
        template.innerHTML = duplicate.chipHtml;
        const chip = template.content.firstElementChild;

        if (!(chip instanceof HTMLElement)) {
          return [];
        }

        chip.setAttribute('size', 'xsmall');
        chip.toggleAttribute('rounded', true);

        if (this.newWindow) {
          for (const link of chip.querySelectorAll<HTMLAnchorElement>(
            'a[href]'
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

        return [chip];
      });
    }
  }

  protected override updated(): void {
    this.stopFading?.();
    const container = this.querySelector<HTMLElement>(
      '.copied-notification-value'
    );
    const row = container?.firstElementChild;

    if (!container || !(row instanceof HTMLElement)) {
      this.stopFading = undefined;
      return;
    }

    // Chips cut off by the box's bottom edge fade out of sight, so out of reach too. Compare shows them all.
    this.stopFading = fadeOverflow(container, row, () => {
      const bottom = container.getBoundingClientRect().bottom;
      const range = document.createRange();
      for (const chip of this.chips) {
        // The chip's host has no box of its own.
        range.selectNodeContents(chip);
        chip.inert = range.getBoundingClientRect().bottom > bottom + 1;
      }
    });
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.stopFading?.();
    this.stopFading = undefined;
  }

  protected override render() {
    if (!this.chips.length) {
      return nothing;
    }

    return html`
      <div class="copied-notification-value upload-duplicates-chips">
        <div
          class="copied-chips"
          @click=${(event: MouseEvent) => this.navigate(event)}
        >
          ${this.chips}
        </div>
      </div>
    `;
  }
}
