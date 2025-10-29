import styles from './option.styles.js';
import {LionOption} from '@lion/ui/listbox.js';

export default class CraftSelect extends LionOption {
  static override get styles() {
    return [...LionOption.styles, styles];
  }
}
