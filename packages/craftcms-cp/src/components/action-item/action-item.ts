import {html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './action-item.styles.js';
import {Variant, type VariantKey} from '@src/types';
import variantsStyles from '@src/styles/variants.styles';
import {classMap} from 'lit/directives/class-map.js';

/**
 * @summary Either a link or button typically used in a menu.
 */
export default class CraftActionItem extends LitElement {
  static override styles = [variantsStyles, styles];
  @property() icon: string | null = null;
  @property() href: string | null = null;
  @property({type: Boolean}) disabled: boolean = false;
  @property({reflect: true}) variant: VariantKey = Variant.Default;
  @property({type: Boolean}) checked: boolean = false;
  @property({type: Boolean}) active: boolean = false;
  @property() type: 'normal' | 'checkbox' = 'normal';

  renderBody() {
    const hasIcon = !!this.querySelector('[slot="icon"]') || !!this.icon;

    return html`
      ${this.type === 'checkbox'
        ? html` <span class="action-item__check">
            <slot name="checkmark">
              ${this.checked
                ? html`<craft-icon name="check"></craft-icon>`
                : nothing}
            </slot>
          </span>`
        : nothing}
      ${hasIcon
        ? html`<span class="action-item__icon">
            <slot name="icon">
              ${this.icon
                ? html`<craft-icon name="${this.icon}"></craft-icon>`
                : nothing}
            </slot>
          </span>`
        : nothing}

      <span class="action-item__label">
        <slot></slot>
      </span>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
      </span>
    `;
  }

  override render() {
    return this.href
      ? html`
          <a
            class="${classMap({
              'action-item': true,
              'action-item--checkbox': this.type === 'checkbox',
            })}"
            href="${this.href}"
          >
            ${this.renderBody()}
          </a>
        `
      : html`
          <button
            type="button"
            class="${classMap({
              'action-item': true,
              'action-item--checkbox': this.type === 'checkbox',
            })}"
            ?disabled="${this.disabled}"
          >
            ${this.renderBody()}
          </button>
        `;
  }
}

if (!customElements.get('craft-action-item')) {
  customElements.define('craft-action-item', CraftActionItem);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-action-item': CraftActionItem;
  }
}
