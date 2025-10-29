import CraftSelect, {CraftSelectInvoker} from './select.component.js';

export * from './select.component.js';
export default CraftSelect;

if (!customElements.get('craft-select')) {
  customElements.define('craft-select', CraftSelect);
}

if (!customElements.get('craft-select-invoker')) {
  customElements.define('craft-select-invoker', CraftSelectInvoker);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select': CraftSelect;
    'craft-select-invoker': CraftSelectInvoker;
  }
}
