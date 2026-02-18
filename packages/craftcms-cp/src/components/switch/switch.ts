import {LionSwitch} from '@lion/ui/switch.js';
import CraftSwitchButton from '../switch-button/switch-button.js';
import styles from './switch.styles.js';
import {baseFieldStyles} from '@src/styles/form.styles';

export default class CraftSwitch extends LionSwitch {
  static override get styles() {
    return [...super.styles, baseFieldStyles, styles];
  }

  override get slots() {
    return {
      ...super.slots,
      input: () => {
        const btnEl = this.createScopedElement('craft-switch-button');
        btnEl.setAttribute('data-tag-name', 'craft-switch-button');
        return btnEl;
      },
    };
  }

  static override get scopedElements() {
    return {
      ...super.scopedElements,
      'craft-switch-button': CraftSwitchButton,
    };
  }
}

if (!customElements.get('craft-switch')) {
  customElements.define('craft-switch', CraftSwitch);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-switch': CraftSwitch;
  }
}
