import CraftInput from './input.component.js';

export * from './input.component.js';
export default CraftInput;

if (!customElements.get('craft-input')) {
  customElements.define('craft-input', CraftInput);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input': CraftInput;
  }
}
