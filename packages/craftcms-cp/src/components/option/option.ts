import CraftOption from './option.component.js';

export * from './option.component.js';
export default CraftOption;

if (!customElements.get('craft-option')) {
  customElements.define('craft-option', CraftOption);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-option': CraftOption;
  }
}
