import CraftTabs from './tabs.component.js';

export * from './tabs.component.js';
export default CraftTabs;

if (!customElements.get('craft-tabs')) {
  customElements.define('craft-tabs', CraftTabs);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-tabs': CraftTabs;
  }
}
