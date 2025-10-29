import {property} from 'lit/decorators.js';
import {html, LitElement} from 'lit';
import styles from './button-group.styles.js';
import type {CSSResultGroup} from 'lit';

/**
 * @summary Wrapper component used to group a set of buttons together.
 *
 * @slot - The default slot.
 */
export default class CraftButtonGroup extends LitElement {
  static override styles: CSSResultGroup = [styles];

  override render() {
    return html`<slot></slot>`;
  }
}
