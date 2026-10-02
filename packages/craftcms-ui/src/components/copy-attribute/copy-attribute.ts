import {classMap} from 'lit/directives/class-map.js';
import {property, query, state} from 'lit/decorators.js';
import '@shoelace-style/shoelace/dist/components/visually-hidden/visually-hidden.js';
import '../tooltip/tooltip.js';
import '../copy-button/copy-button.js';
import {html, LitElement} from 'lit';
import hostStyles from '../../styles/host.styles.js';
import styles from './copy-attribute.styles.js';
import type {CSSResultGroup} from 'lit';
import CraftCopyButton from '../copy-button/copy-button.js';

/**
 * @summary Displays a field handle and allows quick copying
 *
 * @event craft-copy - Emitted when the value is copied to the clipboard.
 * @event craft-error - Emitted when the value could not be copied to the clipboard.
 *
 * @slot - The default slot.
 */
export default class CraftCopyAttribute extends LitElement {
  static override styles: CSSResultGroup = [hostStyles, styles];

  @query('craft-copy-button') protected copyButtonEl!: CraftCopyButton;

  /** The text value to copy */
  @property({type: String})
  value: string = '';

  /** Disables the copy button. */
  @property({type: Boolean, reflect: true})
  disabled: boolean = false;

  protected getId(): string {
    return `attribute-${this.value
      .replace(/([a-z])([A-Z])/g, '$1-$2')
      .replace(/[\s_]+/g, '-')
      .toLowerCase()}`;
  }

  override render() {
    return html`
      <craft-copy-button
        id="${this.getId()}"
        value="${this.value}"
        class=${classMap({
          'copy-attribute': true,
        })}
      >
        ${this.value}
      </craft-copy-button>
    `;
  }
}

if (!customElements.get('craft-copy-attribute')) {
  customElements.define('craft-copy-attribute', CraftCopyAttribute);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-copy-attribute': CraftCopyAttribute;
  }
}
