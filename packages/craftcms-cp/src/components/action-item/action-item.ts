import {html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './action-item.styles.js';
import {Variant, type VariantKey} from '@src/types';
import variantsStyles from '@src/styles/variants.styles';

/**
 * @summary Either a link or button typically used in a menu.
 */
export default class CraftActionItem extends LitElement {
  static override styles = [variantsStyles, styles];
  @property() icon: string | null = null;
  @property() href: string | null = null;
  @property({type: Boolean}) disabled: boolean = false;
  @property() variant: VariantKey = Variant.Default;

  renderBody() {
    return html`
      <span class="action-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon
              ? html`<craft-icon name="${this.icon}"></craft-icon>`
              : nothing}
          </slot>
        </slot>
      </span>

      <slot></slot>

      <span class="action-item__suffix">
        <slot name="suffix"></slot>
      </span>
    `;
  }

  override render() {
    return this.href
      ? html`
          <a class="action-item" href="${this.href}"> ${this.renderBody()} </a>
        `
      : html`
          <button
            type="button"
            class="action-item"
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
