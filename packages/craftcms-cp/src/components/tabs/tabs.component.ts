import {LionTabs} from '@lion/ui/tabs.js';
import styles from './tabs.styles.js';

export default class CraftTabs extends LionTabs {
  static override get styles() {
    return [...super.styles, styles];
  }
}
