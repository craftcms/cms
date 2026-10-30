import {html, LitElement, nothing} from 'lit';
import {property, query, state} from 'lit/decorators.js';
import {type AsyncState, AsyncStates} from '@src/types';
import {
  type HoverIntentMember,
  linkHoverIntent,
} from '@src/utilities/hover-intent.js';
import {t} from '@src/utilities/translate';
import hostStyles from '@src/styles/host.styles';
import styles from './link.styles.js';

import '../button/button.js';
import '../icon/icon.js';
import '../popover/popover.js';
import '../tooltip/tooltip.js';
import type CraftPopover from '../popover/popover.js';

interface LinkKind {
  icon: string;
  heading: string;
}

/**
 * @summary Shows a link on one line, cut off with an ellipsis when it doesn't
 * fit, after a link icon button that opens its details: the full address, a
 * button that opens it and a button that copies it.
 *
 * The link itself is a plain `<a>` in the default slot, so it works before the
 * component loads and keeps its own behavior: middle-click, "Copy link" and
 * `target`/`rel`. The details read the address from it.
 *
 * Resting the pointer on the link or its details button also opens the
 * details.
 * The first one waits out a warm-up; once one has opened, other links open
 * straight away until a cool-down passes. Clicking the details button keeps
 * them open.
 *
 * @example
 * ```html
 * <craft-link>
 *   <a href="https://www.imdb.com/title/tt1674773/">www.imdb.com/title/tt1674773/</a>
 * </craft-link>
 * ```
 *
 * @slot - The `<a>` to show.
 *
 * @event craft-copy - Emitted when the address is copied to the clipboard.
 * @event craft-error - Emitted when the address could not be copied.
 *
 * @cssproperty [--c-link-max-width=100%] - Widest the link gets before it's
 *   cut off. Table cells set it to `200px`, since a column otherwise grows to
 *   fit its longest link. Set it back to `100%` to let the link fill its cell.
 *
 * @csspart text - The line holding the link, which clips it.
 * @csspart details-button - The button that opens the details.
 */
export default class CraftLink extends LitElement {
  static override styles = [hostStyles, styles];

  /**
   * Accessible name for the details button. The link's text is added after
   * it, so each button on a page is told apart.
   */
  @property() label = t('Link details');

  @state() private anchor: HTMLAnchorElement | null = null;

  @state() private copyState: AsyncState = AsyncStates.Idle;

  @state() private detailsOpen = false;

  @query('craft-popover') private detailsPopover?: CraftPopover;

  /** Whether the details are open from hover alone, so leaving closes them. */
  #openedByHover = false;

  #hoverIntent: HoverIntentMember = {
    element: this,
    setOpen: (open) => {
      if (open) {
        this.#openFromHover();
      } else {
        this.detailsPopover?.hide();
      }
    },
  };

  #observer = new MutationObserver(() => this.#readAnchor());

  constructor() {
    super();
    this.addEventListener('pointerenter', this.#onPointerEnter);
    this.addEventListener('pointerleave', this.#onPointerLeave);
    this.addEventListener('pointerdown', this.#cancelHoverOpen);
  }

  override connectedCallback() {
    super.connectedCallback();
    this.#observer.observe(this, {
      subtree: true,
      childList: true,
      characterData: true,
      attributes: true,
      attributeFilter: ['href', 'target', 'rel'],
    });
    this.#readAnchor();
  }

  override disconnectedCallback() {
    this.#observer.disconnect();
    this.#endHover();
    linkHoverIntent.remove(this.#hoverIntent);
    super.disconnectedCallback();
  }

  #readAnchor() {
    this.anchor = this.querySelector('a[href]');
    this.requestUpdate();
  }

  get #text(): string {
    return (this.anchor?.textContent ?? '').replace(/\s+/g, ' ').trim();
  }

  get #scheme(): string {
    return this.anchor?.protocol ?? '';
  }

  get #kind(): LinkKind {
    switch (this.#scheme) {
      case 'mailto:':
        return {icon: 'envelope', heading: t('Email')};
      case 'tel:':
        return {icon: 'phone', heading: t('Phone')};
      default:
        return {icon: 'link', heading: t('Link')};
    }
  }

  /** The address as shown and copied: absolute, and without `mailto:`/`tel:`. */
  get #address(): string {
    const href = this.anchor?.href ?? '';

    if (this.#scheme === 'mailto:' || this.#scheme === 'tel:') {
      return decodeURIComponent(
        href.slice(this.#scheme.length).split('?')[0] ?? ''
      );
    }

    return href;
  }

  get #copyFeedback(): {icon: string; label: string} {
    switch (this.copyState) {
      case AsyncStates.Success:
        return {icon: 'check', label: t('Copied')};
      case AsyncStates.Error:
        return {icon: 'xmark-large', label: t('Copy failed')};
      default:
        return {icon: 'custom-icons/clone-dashed', label: t('Copy')};
    }
  }

  #onCopyStateChange = (event: CustomEvent<{state: AsyncState}>) => {
    this.copyState = event.detail.state;

    if (event.detail.state === AsyncStates.Success) {
      this.dispatchEvent(
        new CustomEvent('craft-copy', {
          bubbles: true,
          composed: true,
          detail: {value: this.#address},
        })
      );
    } else if (event.detail.state === AsyncStates.Error) {
      this.dispatchEvent(
        new CustomEvent('craft-error', {bubbles: true, composed: true})
      );
    }
  };

  #onDetailsToggle = (event: Event) => {
    if (event.target === event.currentTarget) {
      this.detailsOpen = event.type === 'craft-show';

      if (!this.detailsOpen) {
        this.#endHover();
      }
    }
  };

  // Touch has no hover, and a tap would otherwise open the details on its way
  // to following the link.
  #onPointerEnter = (event: PointerEvent) => {
    if (event.pointerType === 'touch') {
      return;
    }

    if (this.#openedByHover || (!this.detailsOpen && this.anchor)) {
      linkHoverIntent.requestOpen(this.#hoverIntent);
    }
  };

  #onPointerLeave = (event: PointerEvent) => {
    if (event.pointerType !== 'touch') {
      linkHoverIntent.requestClose(this.#hoverIntent);
    }
  };

  // A press means the pointer came to click, not to read the details.
  #cancelHoverOpen = () => {
    if (!this.#openedByHover) {
      linkHoverIntent.requestClose(this.#hoverIntent);
    }
  };

  #openFromHover() {
    this.#openedByHover = true;
    document.addEventListener('keydown', this.#onHoverEscape, true);
    this.detailsPopover?.show();
  }

  /** Clicking or focusing into hover-opened details keeps them open. */
  #pin = (event: Event) => {
    if (!this.#openedByHover) {
      return;
    }

    const invoker = this.detailsPopover?.querySelector('[slot="invoker"]');
    const onInvoker = !!invoker && event.composedPath().includes(invoker);

    // Focus lands on the details button before its click, which pins instead.
    if (event.type === 'focusin' && onInvoker) {
      return;
    }

    this.#endHover();

    // The details button would otherwise toggle the details shut.
    if (event.type === 'click' && onInvoker) {
      event.stopPropagation();
    }
  };

  // Focus is usually elsewhere while the pointer rests on the link, out of
  // reach of the popover's own Escape handling.
  #onHoverEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && this.#openedByHover) {
      event.preventDefault();
      event.stopPropagation();
      this.detailsPopover?.hide();
    }
  };

  /** Hands the details back from the hover group, which then leaves them be. */
  #endHover() {
    this.#openedByHover = false;
    linkHoverIntent.notifyClosed(this.#hoverIntent);
    document.removeEventListener('keydown', this.#onHoverEscape, true);
  }

  override render() {
    const kind = this.#kind;
    const copyFeedback = this.#copyFeedback;
    const openLabel = t('Open in a new tab');

    return html`
      ${this.anchor
        ? html`<craft-popover
            label=${kind.heading}
            placement="bottom-start"
            @craft-show=${this.#onDetailsToggle}
            @craft-hide=${this.#onDetailsToggle}
            @click=${{handleEvent: this.#pin, capture: true}}
            @focusin=${this.#pin}
          >
            <craft-button
              slot="invoker"
              type="button"
              variant="plain"
              size="xsmall"
              icon
              part="details-button"
            >
              <craft-icon
                name=${kind.icon}
                label=${[this.label, this.#text].filter(Boolean).join(' ')}
              ></craft-icon>
            </craft-button>
            <div slot="content" class="details">
              <div class="details__header">
                <span class="details__heading">${kind.heading}</span>
                <span class="details__actions">
                  <craft-button
                    id="open-button"
                    class="details__open"
                    href=${this.anchor.href}
                    target="_blank"
                    rel=${this.anchor.rel || nothing}
                    variant="plain"
                    size="small"
                    icon
                  >
                    <craft-icon
                      name="arrow-up-right-from-square"
                      label=${openLabel}
                    ></craft-icon>
                  </craft-button>
                  <craft-button
                    id="copy-button"
                    class="details__copy"
                    type="button"
                    variant="plain"
                    size="small"
                    icon
                    .action=${{type: 'clipboard', value: this.#address}}
                    @craft-state-change=${this.#onCopyStateChange}
                  >
                    <craft-icon
                      name=${copyFeedback.icon}
                      label=${copyFeedback.label}
                    ></craft-icon>
                  </craft-button>
                  <craft-tooltip for="open-button" invoker-relation="label"
                    >${openLabel}</craft-tooltip
                  >
                  <craft-tooltip for="copy-button" invoker-relation="label"
                    >${copyFeedback.label}</craft-tooltip
                  >
                </span>
              </div>
              <a
                class="details__address"
                href=${this.anchor.href}
                target=${this.anchor.target || nothing}
                rel=${this.anchor.rel || nothing}
                >${this.#address}</a
              >
              <span class="details__status" role="status"
                >${this.copyState === AsyncStates.Idle
                  ? ''
                  : copyFeedback.label}</span
              >
            </div>
          </craft-popover>`
        : nothing}
      <span class="text" part="text"
        ><slot @slotchange=${() => this.#readAnchor()}></slot
      ></span>
    `;
  }
}

if (!customElements.get('craft-link')) {
  customElements.define('craft-link', CraftLink);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-link': CraftLink;
  }
}
