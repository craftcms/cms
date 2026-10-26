import {css, html, LitElement} from 'lit';

/**
 * @summary A cell of a `craft-grid-row`.
 *
 * It supplies the cell semantics and nothing else. The grid decides what in
 * the cell takes focus: its interactive element, or the cell itself when it
 * has none.
 *
 * @slot - The cell's content: one link, button or menu, and any text.
 */
export default class CraftGridCell extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }
  `;

  override connectedCallback() {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'gridcell');
    }
  }

  override render() {
    return html`<slot></slot>`;
  }
}

if (!customElements.get('craft-grid-cell')) {
  customElements.define('craft-grid-cell', CraftGridCell);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-grid-cell': CraftGridCell;
  }
}
