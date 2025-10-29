import CraftAvatar from './avatar.component.js';

export * from './avatar.component.js';
export default CraftAvatar;

if (!customElements.get('craft-avatar')) {
  customElements.define('craft-avatar', CraftAvatar);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-avatar': CraftAvatar;
  }
}
