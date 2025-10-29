import CraftTextarea from './textarea.component.js';

export * from './textarea.component.js';
export default CraftTextarea;

if (!customElements.get('craft-textarea')) {
  customElements.define('craft-textarea', CraftTextarea);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-textarea': CraftTextarea;
  }
}
