import {css, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';

import '../icon/icon';

export default class CraftEmpty extends LitElement {
  static override styles = [
    css`
      .cp-empty {
        min-height: calc(200rem / 16);
        display: grid;
        place-items: center;
      }

      .cp-empty__content {
        max-width: 60ch;
        text-align: center;
      }

      .label {
        font-size: 1.25em;
      }
    `,
  ];

  @property() label: string = '';
  @property() icon: string = '';

  protected override render(): unknown {
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
