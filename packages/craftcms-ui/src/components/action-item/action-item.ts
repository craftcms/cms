import {html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './action-item.styles.js';
import {AsyncStates} from '@src/types';
import variantsStyles from '@src/styles/variants.styles';
import {classMap} from 'lit/directives/class-map.js';

import '../shortcut/shortcut.js';
import {Actionable} from '@src/mixins/Actionable';
import {Variant, type VariantValue} from '@src/constants/variants';

/**
 * @summary Either a link or button typically used in a menu.
 */
export default class CraftActionItem extends Actionable(LitElement) {
  static override styles = [variantsStyles, styles];

  /**
   * Delegate focus into the shadow root, so `host.focus()` (used by
   * `craft-action-menu`'s keyboard navigation) lands on the internal
   * button/anchor and native Enter/Space activation keeps working.
   */
  static override shadowRootOptions = {
    ...LitElement.shadowRootOptions,
    delegatesFocus: true,
  };

  @property() icon: string | null = null;
  /**
   * Optional Craft color name used to tint the item's icon. The icon renders
   * with `currentColor`, so we set the icon element's `color` to a matching
   * `--c-color-*` token.
   */
  @property({attribute: 'icon-color'}) iconColor: string | null = null;
  @property() href: string | null = null;
  @property({type: Boolean}) disabled: boolean = false;
  @property({reflect: true}) variant: VariantValue = Variant.Neutral;
  @property({type: Boolean}) checked: boolean = false;
  @property({type: Boolean}) active: boolean = false;
  @property() type: 'button' | 'checkbox' = 'button';

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

  renderCheckbox() {
    return html`<span class="action-item__check">
      <slot name="checkmark">
        ${this.checked ? html`<craft-icon name="check"></craft-icon>` : nothing}
      </slot>
    </span>`;
  }

  renderIcon() {
    switch (this.actionState) {
      case AsyncStates.Loading:
        return html`<craft-spinner style="--size: 0.8em"></craft-spinner>`;
      case AsyncStates.Success:
        return html`<craft-icon
          name="check"
          style="color: var(--c-color-success-on-normal)"
        ></craft-icon>`;
      case AsyncStates.Error:
        return html`<craft-icon
          name="xmark"
          style="color: var(--c-color-danger-on-normal)"
        ></craft-icon>`;
      default:
        return html`
          <slot name="icon">
            ${this.icon
              ? html`<craft-icon
                  name="${this.icon}"
                  style="${this.iconColor
                    ? `color: var(--c-color-${this.iconColor}-on-normal, currentColor)`
                    : nothing}"
                ></craft-icon>`
              : nothing}
          </slot>
        `;
    }
  }

  renderPrefix() {
    const hasIcon = !!this.querySelector('[slot="icon"]') || !!this.icon;

    return html`
      ${this.type === 'checkbox' ? this.renderCheckbox() : nothing}
      ${hasIcon
        ? html`<div class="action-item__icon">${this.renderIcon()}</div>`
        : nothing}
    `;
  }

  renderBody() {
    return html`
      ${this.renderPrefix()}

      <span class="action-item__label">
        ${this.feedbackMessage ? this.feedbackMessage : html`<slot></slot>`}
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
