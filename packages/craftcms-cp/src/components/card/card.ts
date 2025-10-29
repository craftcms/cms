import CraftCard from './card.component.js';

export * from './card.component.js';
export default CraftCard;

if (!customElements.get('craft-card')) {
  customElements.define('craft-card', CraftCard);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-card': CraftCard;
  }
}
