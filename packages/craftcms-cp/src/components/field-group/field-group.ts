import {css, html, LitElement} from 'lit';

export default class CraftFieldGroup extends LitElement {
  static override styles = [
    css`
      :host {
        display: grid;
        gap: var(--c-spacing-md);
      }
    `,
  ];

  override render() {
    return html`<slot></slot>`;
  }
}

if (!customElements.get('craft-field-group')) {
  customElements.define('craft-field-group', CraftFieldGroup);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-field-group': CraftFieldGroup;
  }
}
