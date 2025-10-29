import CraftLightswitch from './switch.component.js';
import CraftSwitch from './switch.component.js';

export * from './switch.component.js';
export default CraftSwitch;

if (!customElements.get('craft-switch')) {
  customElements.define('craft-switch', CraftSwitch);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-switch': CraftSwitch;
  }
}
