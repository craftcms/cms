import CraftCopyButton from './copy-button.component.js';

export * from './copy-button.component.js';
export default CraftCopyButton;

if (!customElements.get('craft-copy-button')) {
  customElements.define('craft-copy-button', CraftCopyButton);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-copy-button': CraftCopyButton;
  }
}
