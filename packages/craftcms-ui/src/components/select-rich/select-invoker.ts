import {LionSelectInvoker} from '@lion/ui/select-rich.js';
import {html} from 'lit';
import styles from './select-invoker.styles.js';
import '../icon/icon.js';

/**
 * @summary The invoker (trigger button) for {@link CraftSelectRich}. Extends
 * Lion's select invoker to append a `chevron-down` indicator, which is hidden
 * when the select has only a single option (and therefore can't be changed).
 *
 * Not globally registered — it is wired in as the `lion-select-invoker` scoped
 * element of `CraftSelectRich` rather than used directly.
 *
 * @since 1.0
 */
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
