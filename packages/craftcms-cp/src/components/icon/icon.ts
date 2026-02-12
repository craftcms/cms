import WaIcon from '@awesome.me/webawesome/dist/components/icon/icon.js';
import {css} from 'lit';

/**
 * craft-icon is just an alias to wa-icon from web awesome.
 *
 * Anything you can do over there you can do here.
 */
export default class CraftIcon extends WaIcon {
  static override get styles() {
    return [
      WaIcon.styles,
      css`
        :host {
          font-size: 0.8em;
        }
      `,
    ];
  }
}

// Alias to `craft-icon`
if (!customElements.get('craft-icon')) {
  customElements.define('craft-icon', CraftIcon);
}
