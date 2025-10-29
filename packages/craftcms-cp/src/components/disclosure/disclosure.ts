import CraftDisclosure from './disclosure.component.js';

export * from './disclosure.component.js';

export default CraftDisclosure;

if (!customElements.get('craft-disclosure')) {
  customElements.define('craft-disclosure', CraftDisclosure);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-disclosure': CraftDisclosure;
  }
}
