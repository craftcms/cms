import {property} from 'lit/decorators.js';
import {html, LitElement, nothing} from 'lit';
import styles from './chip.styles.js';
import type {CSSResultGroup} from 'lit';
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

  override render() {
    // query the element Light DOM children for slotted elements
    const hasSlottedPrefixes = !!this.querySelector('[slot="prefix"]');
    const hasSlottedSuffixes = !!this.querySelector('[slot="suffix"]');

    return html`
      <div
        class="${classMap({
          chip: true,
          'chip--small': this.size === 'small',
          'chip--medium': this.size === 'medium',
          'chip--large': this.size === 'large',
          'chip--plain': this.variant === 'plain',
        })}"
      >
        ${hasSlottedPrefixes
          ? html`<div class="chip__prefix"><slot name="prefix"></slot></div>`
          : nothing}
        <div class="chip__body">
          <slot></slot>
        </div>
        ${hasSlottedSuffixes
          ? html`<div class="chip__suffix"><slot name="suffix"></slot></div>`
          : nothing}
      </div>
    `;
  }
}
