import {css, html, LitElement} from 'lit';

/**
 * @summary A row of a `craft-grid`, holding its `craft-grid-cell`s.
 *
 * It supplies the row semantics and nothing else: style the host to lay its
 * cells out.
 *
 * @slot - The `craft-grid-cell`s making up the row.
 */
export default class CraftGridRow extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }
  `;

  override connectedCallback() {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'row');
    }
  }

  override render() {
    return html`<slot></slot>`;
  }
}

if (!customElements.get('craft-grid-row')) {
  customElements.define('craft-grid-row', CraftGridRow);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-grid-row': CraftGridRow;
  }
}
