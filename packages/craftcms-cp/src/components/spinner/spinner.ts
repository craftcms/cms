import CraftSpinner from './spinner.component.js';

export * from './spinner.component.js';

export default CraftSpinner;

if (!customElements.get('craft-spinner')) {
  customElements.define('craft-spinner', CraftSpinner);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-spinner': CraftSpinner;
  }
}
