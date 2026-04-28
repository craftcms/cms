import {css} from 'lit';
import WaDrawer from '@awesome.me/webawesome/dist/components/drawer/drawer.js';

/**
 * craft-drawer extends wa-drawer from web awesome.
 *
 * Anything you can do over there you can do here.
 */
export default class CraftDrawer extends WaDrawer {
  static override get styles() {
    return [
      WaDrawer.styles,
      css`
        :host {
          --wa-color-surface-raised: var(--c-surface-raised);
          --spacing: var(--c-spacing-lg);
        }
      `,
    ];
  }
}

if (!customElements.get('craft-drawer')) {
  customElements.define('craft-drawer', CraftDrawer);
}
