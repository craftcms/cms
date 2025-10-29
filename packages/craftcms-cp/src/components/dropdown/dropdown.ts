import CraftDropdown, {CraftDropdownItem} from './dropdown.component.js';

export * from './dropdown.component.js';
export default CraftDropdown;

if (!customElements.get('craft-dropdown')) {
  customElements.define('craft-dropdown', CraftDropdown);
}

if (!customElements.get('craft-dropdown-item')) {
  customElements.define('craft-dropdown-item', CraftDropdownItem);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-dropdown': CraftDropdown;
    'craft-dropdown-item': CraftDropdownItem;
  }
}
