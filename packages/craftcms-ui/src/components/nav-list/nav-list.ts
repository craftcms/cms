import {css, html, LitElement} from 'lit';

export default class CraftNavList extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }

    .nav-list {
      display: grid;
      margin: 0;
      padding: 0;
      list-style: none;
    }
  `;

  override render() {
    return html`
      <ul class="nav-list">
        <slot></slot>
      </ul>
    `;
  }
}

if (!customElements.get('craft-nav-list')) {
  customElements.define('craft-nav-list', CraftNavList);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-nav-list': CraftNavList;
  }
}
