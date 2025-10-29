import CraftButton from './button.component.js';

export * from './button.component.js';
export default CraftButton;

if (!customElements.get('craft-button')) {
  customElements.define('craft-button', CraftButton);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-button': CraftButton;
  }
}
