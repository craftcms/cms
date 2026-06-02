import {html, css, LitElement} from 'lit';
import {Color, type ColorKey} from '@src/constants/colors';
import {property} from 'lit/decorators.js';

export default class CraftStatusBadge extends LitElement {
  static override styles = [
    css`
      .status-badge {
        display: inline-flex;
        border-radius: var(--c-radius-full);
        padding: 0 0.25em;
        font-size: 0.9em;
        align-items: center;
        font-weight: 500;

        background-color: var(--c-color-fill-quiet);
        color: var(--c-color-on-quiet);
        border: 1px solid var(--c-color-border-quiet);
      }

      .status-badge__body {
        display: inline-flex;
        padding-inline: 0.25em;
      }
    `,
  ];
  @property() color: ColorKey = Color.Gray;

  protected override render(): unknown {
    return html`
      <span class="status-badge" data-color="${this.color}">
        <slot name="prefix"></slot>
        <span class="status-badge__body"><slot></slot></span>
        <slot name="suffix"></slot>
      </span>
    `;
  }
}

if (!customElements.get('craft-status-badge')) {
  customElements.define('craft-status-badge', CraftStatusBadge);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-status-badge': CraftStatusBadge;
  }
}
