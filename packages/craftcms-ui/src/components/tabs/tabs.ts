import {LionTabs} from '@lion/ui/tabs.js';
import styles from './tabs.styles.js';

export default class CraftTabs extends LionTabs {
  private __lightDomObserver = new MutationObserver(() => {
    this.shadowRoot
      ?.querySelector('slot[name="tab"]')
      ?.dispatchEvent(new Event('slotchange'));
  });

  static override get styles() {
    return [...super.styles, styles];
  }

  override get tabs(): HTMLButtonElement[] {
    const tabs = super.tabs;

    return tabs.slice(0, Math.min(tabs.length, super.panels.length));
  }

  override get panels(): HTMLElement[] {
    const panels = super.panels;

    return panels.slice(0, Math.min(super.tabs.length, panels.length));
  }

  override connectedCallback(): void {
    super.connectedCallback();
    this.__lightDomObserver.observe(this, {childList: true});
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.__lightDomObserver.disconnect();
  }
}

if (!customElements.get('craft-tabs')) {
  customElements.define('craft-tabs', CraftTabs);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-tabs': CraftTabs;
  }
}
