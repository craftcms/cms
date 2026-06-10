import {property} from 'lit/decorators.js';
import type {CSSResultGroup} from 'lit';
import {html, LitElement, nothing} from 'lit';
import styles from './chip.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import {Appearance, type AppearanceValue} from '@src/constants/appearances';
import {Variant, type VariantValue} from '@src/constants/variants';
import type {SizeValue} from '@src/constants/size';

/**
 * @summary A compact, inline element that pairs a label with an optional
 * prefix (icon, status indicator, thumbnail, …) and suffix (e.g. an action
 * button). Used for element chips, status chips, and similar UI.
 *
 * The prefix is only rendered when the `prefix` or `icon` slot is filled or the
 * `icon` attribute is set; the suffix is only rendered when the `suffix` slot is
 * filled.
 *
 * @slot - The chip's body/label content.
 * @slot prefix - Content shown before the body, e.g. a status indicator or
 *   thumbnail. Takes precedence over the `icon` slot/attribute.
 * @slot icon - Custom icon content shown in the prefix, as an alternative to the
 *   `icon` attribute.
 * @slot suffix - Content shown after the body, e.g. an action button.
 *
 * @csspart chip - The outer chip wrapper.
 * @csspart prefix - The prefix container.
 * @csspart suffix - The suffix container.
 *
 * @cssproperty --c-chip-height - Minimum height of the chip. Defaults to `--c-size-control-sm`.
 * @cssproperty --c-chip-radius - Corner radius. Defaults to `--c-radius-md`.
 * @cssproperty --c-chip-spacing-inline - Inline (horizontal) padding. Defaults to `0`.
 * @cssproperty --c-chip-spacing-block - Block (vertical) padding. Defaults to `--c-spacing-sm`.
 * @cssproperty --c-chip-shadow - Box shadow. Defaults to `--c-shadow-sm`.
 * @cssproperty --c-chip-border-width - Border width. Defaults to `1px`.
 * @cssproperty --c-chip-border-style - Border style. Defaults to `solid`.
 */
export default class CraftChip extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Size of the chip. */
  @property() size: SizeValue | '' = '';

  /** Variant of the chip. `plain` will render with no border or padding */
  @property({reflect: true}) variant: VariantValue = Variant.Neutral;

  /** Appearance of the chip. Defaults to `outline-fill`. */
  @property({reflect: true}) appearance: AppearanceValue =
    Appearance.OutlineFill;

  /** Shortcut for adding an icon as the prefix */
  @property() icon: string | null = null;

  renderPrefix() {
    return html`<div class="cp-chip__prefix" part="prefix">
      <slot name="prefix">
        <slot name="icon">
          ${this.icon
            ? html`<craft-icon name="${this.icon}"></craft-icon>`
            : nothing}
        </slot>
      </slot>
    </div>`;
  }

  override render() {
    // query the element Light DOM children for slotted elements
    const renderPrefix =
      !!this.querySelector('[slot="prefix"]') ||
      !!this.querySelector('[slot="icon"]') ||
      this.icon;
    const renderSuffix = !!this.querySelector('[slot="suffix"]');

    return html`
      <div
        part="chip"
        class="${classMap({
          'cp-chip': true,
          'cp-chip--small': this.size === 'small',
          'cp-chip--medium': this.size === 'medium',
          'cp-chip--large': this.size === 'large',
          'cp-chip--plain': this.appearance === Appearance.Plain,
        })}"
      >
        ${renderPrefix ? this.renderPrefix() : nothing}
        <div class="cp-chip__body">
          <slot></slot>
        </div>
        ${renderSuffix
          ? html`<div class="cp-chip__suffix" part="suffix">
              <slot name="suffix"></slot>
            </div>`
          : nothing}
      </div>
    `;
  }
}

if (!customElements.get('craft-chip')) {
  customElements.define('craft-chip', CraftChip);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-chip': CraftChip;
  }
}
