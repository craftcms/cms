import CraftStatus from './status.component.js';

export * from './status.component.js';
export default CraftStatus;

if (!customElements.get('craft-status')) {
  customElements.define('craft-status', CraftStatus);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-status': CraftStatus;
  }
}
