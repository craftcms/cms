import WaIcon from '@awesome.me/webawesome/dist/components/icon/icon.js';
import styles from './icon.styles.js';

/**
 * craft-icon is just an alias to wa-icon from web awesome.
 *
 * Anything you can do over there you can do here.
 */
export default class CraftIcon extends WaIcon {
  static override get styles() {
    return [WaIcon.styles, styles];
  }
}

// Alias to `craft-icon`
if (!customElements.get('craft-icon')) {
  customElements.define('craft-icon', CraftIcon);
}
