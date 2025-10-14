import {LitElement, html, css} from 'lit';
import {customElement} from 'lit/decorators.js';

@customElement('cp-header')
export default class Header extends LitElement {
  static styles = css`
    :host {
      display: flex;
      background-color: var(--c-bg-sunken);
      padding-block: var(--c-spacing-sm);
      padding-inline: var(--c-spacing-md);
    }
  `;

  render() {
    return html` <slot></slot> `;
  }
}
