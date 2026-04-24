import {html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './action-item.styles.js';
import {Variant, type VariantKey} from '@src/types';
import variantsStyles from '@src/styles/variants.styles';
import {classMap} from 'lit/directives/class-map.js';

import '../shortcut/shortcut.js';

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

  @property({
    converter: {
      fromAttribute(value: string | null) {
        if (value === null) return null;

        // Try to parse as JSON object first
        try {
          const parsed = JSON.parse(value);
          if (typeof parsed === 'object' && parsed !== null) {
            return parsed;
          }
        } catch {
          // Not JSON — treat as plain string shortcut
        }

        return value; // plain string like "k" or "ctrl+k"
      },
      toAttribute(value) {
        if (value === null) return null;
        if (typeof value === 'string') return value;
        return JSON.stringify(value);
      },
    },
  })
  shortcut: string | {alt?: boolean; shift?: boolean; key: string} | null =
    null;

  renderShortcut() {
    if (typeof this.shortcut === 'string') {
      return html`<craft-shortcut>${this.shortcut}</craft-shortcut>`;
    }

    if (this.shortcut !== null) {
      return html`<craft-shortcut
        ?alt="${this.shortcut.alt ?? false}"
        ?shift="${this.shortcut.shift ?? false}"
        >${this.shortcut.key}</craft-shortcut
      >`;
    }

    return nothing;
  }

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
        ${this.shortcut ? this.renderShortcut() : nothing}
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
