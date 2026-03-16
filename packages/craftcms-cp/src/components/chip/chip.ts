import {property} from 'lit/decorators.js';
import type {CSSResultGroup} from 'lit';
import {html, LitElement, nothing} from 'lit';
import styles from './chip.styles.js';
import {classMap} from 'lit/directives/class-map.js';

/**
 * @summary Short summary of the component's intended use.
 *
 * @event craft-event-name - Emitted as an example.
 *
 * @slot - The default slot.
 * @slot example - An example slot.
 *
 * @csspart base - The component's base wrapper.
 *
 * @cssproperty --example - An example CSS custom property.
 */
export default class CraftChip extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Size of the chip. */
  @property() size: 'small' | 'medium' | 'large' | '' = '';

  /** Variant of the chip. `plain` will render with no border or padding */
  @property() variant: 'plain' | '' = '';

  /** Shortcut for adding an icon as the prefix */
  @property() icon: string | null = null;

  renderPrefix() {
    return html`<div class="chip__prefix" part="prefix">
      <slot name="prefix">
        ${this.icon
          ? html`<craft-icon name="${this.icon}"></craft-icon>`
          : nothing}
      </slot>
    </div>`;
  }

  override render() {
    // query the element Light DOM children for slotted elements
    const renderPrefix = !!this.querySelector('[slot="prefix"]') || this.icon;
    const renderSuffix = !!this.querySelector('[slot="suffix"]');

    return html`
      <div
        part="chip"
        class="${classMap({
          chip: true,
          'chip--small': this.size === 'small',
          'chip--medium': this.size === 'medium',
          'chip--large': this.size === 'large',
          'chip--plain': this.variant === 'plain',
        })}"
      >
        ${renderPrefix ? this.renderPrefix() : nothing}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${renderSuffix
          ? html`<div class="chip__suffix" part="suffix">
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
