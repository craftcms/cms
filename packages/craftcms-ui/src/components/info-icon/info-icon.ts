import {t} from '@src/utilities/translate';
import {css, html, LitElement} from 'lit';
import {property, query} from 'lit/decorators.js';

import '../button/button';
import '../icon/icon';
import '../tooltip/tooltip';
import type CraftTooltip from '../tooltip/tooltip';

/**
 * @summary A small "more info" button that reveals explanatory text in a
 * tooltip. Use it for a detail that helps but should not take up room beside
 * the thing it explains.
 *
 * Only one info icon is open at a time — opening one closes any other, so the
 * page never accumulates tooltips. A click outside the icon and its tooltip
 * closes it too.
 *
 * The tooltip opens on click rather than hover, so the text is reachable
 * without a pointer and stays put long enough to read.
 *
 * @slot - The explanatory text shown in the tooltip.
 */
export default class CraftInfoIcon extends LitElement {
  static override styles = css`
    :host {
      display: inline-flex;
    }
  `;

  static #openInstance: CraftInfoIcon | null = null;

  /** Accessible name for the button that opens the tooltip. */
  @property() label = t('More Info');

  /** Name of the icon shown on the button. */
  @property() icon = 'circle-info';

  /** Prevents the tooltip from being opened. */
  @property({type: Boolean, reflect: true}) disabled = false;

  @query('craft-tooltip') protected tooltip!: HTMLElement;

  #eventController = new AbortController();

  /** Listens for a click outside while the tooltip is open. */
  #outsideController: AbortController | null = null;

  #buttonId = `info-icon-${Math.random().toString(36).slice(2, 8)}`;

  override connectedCallback() {
    super.connectedCallback();

    // Recreate event controller if it was aborted
    if (this.#eventController.signal.aborted) {
      this.#eventController = new AbortController();
    }

    const {signal} = this.#eventController;

    this.addEventListener(
      'craft-show',
      () => {
        if (
          CraftInfoIcon.#openInstance &&
          CraftInfoIcon.#openInstance !== this
        ) {
          const otherTooltip =
            CraftInfoIcon.#openInstance.renderRoot.querySelector<CraftTooltip>(
              'craft-tooltip'
            );
          otherTooltip?.hide();
        }
        CraftInfoIcon.#openInstance = this;
        this.#watchOutside();
      },
      {signal}
    );

    this.addEventListener(
      'craft-after-hide',
      () => {
        if (CraftInfoIcon.#openInstance === this) {
          CraftInfoIcon.#openInstance = null;
        }
        this.#outsideController?.abort();
        this.#outsideController = null;
      },
      {signal}
    );
  }

  /** Hides the tooltip when the pointer goes down anywhere but on the icon or in its tooltip. */
  #watchOutside(): void {
    this.#outsideController?.abort();
    this.#outsideController = new AbortController();

    document.addEventListener(
      'pointerdown',
      (event) => {
        if (!event.composedPath().includes(this)) {
          void this.renderRoot
            .querySelector<CraftTooltip>('craft-tooltip')
            ?.hide();
        }
      },
      {capture: true, signal: this.#outsideController.signal}
    );
  }

  override disconnectedCallback() {
    if (CraftInfoIcon.#openInstance === this) {
      CraftInfoIcon.#openInstance = null;
    }
    this.#outsideController?.abort();
    this.#outsideController = null;
    this.#eventController.abort();
    super.disconnectedCallback();
  }

  override render() {
    return html`
      <div class="cp-info-icon">
        <craft-button
          type="button"
          icon
          size="zero"
          variant="plain"
          id="${this.#buttonId}"
        >
          <craft-icon name="${this.icon}" label="${this.label}"></craft-icon>
        </craft-button>

        <craft-tooltip trigger="click" for="${this.#buttonId}"
          ><slot></slot
        ></craft-tooltip>
      </div>
    `;
  }
}

if (!customElements.get('craft-info-icon')) {
  customElements.define('craft-info-icon', CraftInfoIcon);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-info-icon': CraftInfoIcon;
  }
}
