import {css, html, LitElement} from 'lit';
import CraftInfoIcon from '@src/components/info-icon/info-icon';
import {property} from 'lit/decorators.js';

export default class CraftVisuallyHidden extends LitElement {
  static override styles = css`
    :host(:not([debug])) {
      position: absolute;
      width: 1px;
      height: 1px;
      overflow: hidden;
      clip: rect(0 0 0 0);
      clip-path: inset(50%);
      white-space: nowrap;
    }
  `;

  @property({type: Boolean, reflect: true}) debug = false;

  protected override render(): unknown {
    return html`<slot></slot>`;
  }
}

if (!customElements.get('craft-visually-hidden')) {
  customElements.define('craft-visually-hidden', CraftVisuallyHidden);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-visually-hidden': CraftVisuallyHidden;
  }
}
