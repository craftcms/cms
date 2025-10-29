import CraftButtonGroup from './button-group.component.js';

export * from './button-group.component.js';
export default CraftButtonGroup;

if (!customElements.get('craft-button-group')) {
  customElements.define('craft-button-group', CraftButtonGroup);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-button-group': CraftButtonGroup;
  }
}
