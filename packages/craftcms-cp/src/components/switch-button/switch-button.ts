import CraftSwitchButton from './switch-button.component.js';

export * from './switch-button.component.js';
export default CraftSwitchButton;

if (!customElements.get('craft-switch-button')) {
  customElements.define('craft-switch-button', CraftSwitchButton);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-switch-button': CraftSwitchButton;
  }
}
