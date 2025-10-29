import CraftTab from './tab.component.js';
import './tab.css';

export * from './tab.component.js';
export default CraftTab;

if (!customElements.get('craft-tab')) {
  customElements.define('craft-tab', CraftTab);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-tab': CraftTab;
  }
}
