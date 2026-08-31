import {html, LitElement} from 'lit';
import {property, query} from 'lit/decorators.js';
import componentStyles from './spinner.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles';

import '../visually-hidden/visually-hidden';

/**
 * @summary An indeterminate loading indicator, for work whose progress cannot
 * be measured. Use `craft-progress` or `craft-progress-bar` when it can.
 *
 * The spinner is presentational on its own. Slot text to say what is loading,
 * which is announced but not shown.
 *
 * @slot - A description of what is loading, visually hidden.
 *
 * @fires show - Emitted when the spinner is shown via `show()`.
 * @fires hide - Emitted when the spinner is hidden via `hide()`.
 */
export default class CraftSpinner extends LitElement {
  static override styles = [visuallyHiddenStyles, componentStyles];

  /**
   * Whether the spinner is shown. Hiding keeps it in the layout rather than
   * removing it, so surrounding content does not jump as work starts and
   * stops.
   */
  @property({reflect: true, type: Boolean})
  visible: boolean = true;

  @query('.wrapper')
  wrapper!: HTMLElement | null;

  show() {
    this.visible = true;
    this.dispatchEvent(new CustomEvent('show'));
  }

  hide() {
    this.visible = false;
    this.dispatchEvent(new CustomEvent('hide'));
  }

  override focus() {
    this.wrapper?.focus();
  }

  protected override render() {
    return html`
      <div
        tabindex="-1"
        class="${classMap({
          wrapper: true,
          hidden: !this.visible,
        })}"
      >
        <div class="spinner"></div>
        <span class="cp-visually-hidden"><slot /></span>
      </div>
    `;
  }
}

if (!customElements.get('craft-spinner')) {
  customElements.define('craft-spinner', CraftSpinner);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-spinner': CraftSpinner;
  }
}
