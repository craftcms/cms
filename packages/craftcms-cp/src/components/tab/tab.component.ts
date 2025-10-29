import {property} from 'lit/decorators.js';
import {html, LitElement} from 'lit';
import styles from './tab.styles.js';
import type {CSSResultGroup} from 'lit';

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
export default class CraftTab extends LitElement {
  static override styles: CSSResultGroup = [styles];

  override render() {
    return html`<slot></slot> `;
  }
}
