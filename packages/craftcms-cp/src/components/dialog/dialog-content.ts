import {type CSSResultGroup, html, LitElement} from 'lit';
import styles from './dialog-content.styles.js';

/**
 * @summary The visible content panel for craft-dialog. Place this inside
 * the `content` slot of a craft-dialog component.
 *
 * @slot header - The dialog title or header content.
 * @slot - The dialog body content.
 * @slot footer - Actions or buttons shown at the bottom of the dialog.
 *
 * @csspart header - The header wrapper.
 * @csspart body - The body wrapper.
 * @csspart footer - The footer wrapper.
 */
export default class CraftDialogContent extends LitElement {
  static override get styles(): CSSResultGroup {
    return [styles];
  }

  override connectedCallback() {
    super.connectedCallback();
    this.slot = 'content';
  }

  override render() {
    return html`
      <div class="header" part="header">
        <slot name="header"></slot>
      </div>
      <div class="body" part="body">
        <slot></slot>
      </div>
      <div class="footer" part="footer">
        <slot name="footer"></slot>
      </div>
    `;
  }
}

if (!customElements.get('craft-dialog-content')) {
  customElements.define('craft-dialog-content', CraftDialogContent);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-dialog-content': CraftDialogContent;
  }
}
