import {LionSelectInvoker} from '@lion/ui/select-rich.js';
import {html} from 'lit';
import styles from './select-invoker.styles.js';
import '../icon/icon.js';

export default class CraftSelectInvoker extends LionSelectInvoker {
  static override get styles() {
    return [...super.styles, styles];
  }

  override _afterTemplate() {
    return html`${!this.singleOption
      ? html`<craft-icon class="indicator" name="chevron-down"></craft-icon>`
      : ''}`;
  }
}

// Not globally registered — used only as a scoped element within CraftSelectRich
