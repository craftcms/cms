import {css, html, LitElement} from 'lit';

export default class CraftFieldGroup extends LitElement {
  protected override render() {
    return html`
      <style>
        craft-field-group {
          display: grid;
          gap: var(--gap, var(--c-spacing-lg));
        }
      </style>
      <slot></slot>
    `;
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
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
