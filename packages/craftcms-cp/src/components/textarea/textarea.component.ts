import styles from './textarea.styles.js';
import {inputStyles} from '../../styles/form.styles.js';
import {LionTextarea} from '@lion/ui/textarea.js';

export default class CraftTextarea extends LionTextarea {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }
}
