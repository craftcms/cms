import {css, html, LitElement} from 'lit';

export default class CraftNavigation extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }

    .nav {
      display: grid;
    }
  `;

  override render() {
    return html`
      <div class="nav">
        <slot></slot>
      </div>
    `;
  }
}

if (!customElements.get('craft-navigation')) {
  customElements.define('craft-navigation', CraftNavigation);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-navigation': CraftNavigation;
  }
}
