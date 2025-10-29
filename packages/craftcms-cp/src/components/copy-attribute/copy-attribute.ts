import CraftCopyAttribute from './copy-attribute.component.js';

export * from './copy-attribute.component.js';
export default CraftCopyAttribute;

if (!customElements.get('craft-copy-attribute')) {
  customElements.define('craft-copy-attribute', CraftCopyAttribute);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-copy-attribute': CraftCopyAttribute;
  }
}
