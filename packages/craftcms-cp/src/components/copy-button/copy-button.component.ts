import {property, state} from 'lit/decorators.js';
import {html, LitElement} from 'lit';
import styles from './copy-button.styles.js';
import '@shoelace-style/shoelace/dist/components/visually-hidden/visually-hidden.js';
import type {CSSResultGroup} from 'lit';

/**
 * @summary Copy values to the clipboard on click.
 *
 * @event craft-copy - Emitted when the value is copied to the clipboard.
 * @event craft-error - Emitted when the value could not be copied to the clipboard.
 *
 * @slot - The default slot.
 *
 * @csspart button - The main button element.
 */
export default class CraftCopyButton extends LitElement {
  static override styles: CSSResultGroup = [styles];

  @state() isCopying = false;

  /** Value to copy on click */
  @property({type: String}) value = '';

  @property({type: Boolean}) disabled = false;

  async copyValue() {
    if (this.isCopying || this.disabled) {
      return;
    }

    this.isCopying = true;

    try {
      await navigator.clipboard.writeText(this.value);
      this.dispatchEvent(
        new CustomEvent('craft-copy', {
          bubbles: true,
          cancelable: false,
          composed: true,
          detail: {
            value: this.value,
          },
        })
      );
    } catch (error) {
      this.dispatchEvent(
        new CustomEvent('craft-error', {
          cancelable: false,
          composed: true,
          bubbles: true,
        })
      );
    } finally {
      this.isCopying = false;
    }
  }

  override render() {
    return html`
      <button
        type="button"
        @click="${this.copyValue}"
        ?disabled=${this.disabled}
        class="copy-button"
        part="button"
      >
        <slot></slot>
        <sl-visually-hidden>Copy to clipboard</sl-visually-hidden>
      </button>
    `;
  }
}
