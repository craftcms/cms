import CraftChip from './chip.component.js';

export * from './chip.component.js';
export default CraftChip;

if (!customElements.get('craft-chip')) {
  customElements.define('craft-chip', CraftChip);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-chip': CraftChip;
  }
}
