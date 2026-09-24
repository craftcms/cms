import {css, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';

import '../icon/icon';

/**
 * @summary An empty state: a centred message, an optional graphic, and room
 * for whatever the person should do next.
 *
 * Shown where a list, an index, or a panel has nothing in it — a place that
 * would otherwise be blank, which reads as broken rather than as empty.
 *
 * @slot - Content shown after the message, usually the action to take.
 * @slot graphic - Artwork shown above the message, replacing the `icon`.
 * @slot content - The message region, replacing the `label`.
 */
export default class CraftEmpty extends LitElement {
  static override styles = [
    css`
      .cp-empty {
        min-height: calc(120rem / 16);
        display: grid;
        place-items: center;
        border-radius: var(--c-radius-md);
        background-color: var(--c-color-neutral-fill-quiet);
        color: var(--c-color-neutral-on-quiet);
        padding-block: var(--c-spacing-lg);
      }

      .cp-empty__content {
        display: grid;
        gap: var(--c-spacing-md);
        justify-items: center;
        max-width: 60ch;
        text-align: center;
      }

      .label {
        margin: 0;
        font-size: 1.25em;
      }
    `,
  ];

  /** The message. Say what is missing, not that something is missing. */
  @property() label: string = '';

  /** Name of an icon shown above the message. */
  @property() icon: string = '';

  protected override render() {
    return html`
      <div class="cp-empty">
        <div class="cp-empty__content">
          <slot name="graphic">
            ${this.icon
              ? html`
                  <craft-icon
                    class="cp-empty__icon"
                    name="${this.icon}"
                    style="font-size: calc(48rem / 16)"
                  ></craft-icon>
                `
              : nothing}
          </slot>
          <slot name="content">
            <p class="label">${this.label}</p>
          </slot>

          <slot></slot>
        </div>
      </div>
    `;
  }
}

if (!customElements.get('craft-empty')) {
  customElements.define('craft-empty', CraftEmpty);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-empty': CraftEmpty;
  }
}
