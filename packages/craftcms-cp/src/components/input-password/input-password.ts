import CraftInputPassword from './input-password.component.js';

export * from './input-password.component.js';
export default CraftInputPassword;

if (!customElements.get('craft-input-password')) {
  customElements.define('craft-input-password', CraftInputPassword);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-password': CraftInputPassword;
  }
}
