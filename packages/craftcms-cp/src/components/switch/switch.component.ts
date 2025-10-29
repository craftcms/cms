import {LionSwitch} from '@lion/ui/switch.js';
import CraftSwitchButton from '../switch-button/switch-button.component.js';
import styles from './switch.styles.js';

export default class CraftSwitch extends LionSwitch {
  static override get styles() {
    return [...super.styles, styles];
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
