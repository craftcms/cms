import styles from './select.styles.js';
import {LionSelect} from '@lion/ui/select.js';
import {html} from 'lit';
import '../option/option.js';
import '../icon/icon.js';
import {property} from 'lit/decorators.js';

/**
 * @summary A dropdown built on the native `<select>`, with Craft's field
 * chrome and a chevron indicator around it.
 *
 * The options are real `<option>` elements, so the browser supplies the
 * dropdown — which is what you want on touch devices, where the native picker
 * is far better than anything a component can draw.
 *
 * Reach for `craft-select-rich` when an option needs more than text: an icon,
 * a status, a hint. Reach for `craft-combobox` when the list is long enough to
 * need filtering.
 *
 * @slot input - The native `<select>` and its options.
 * @slot label - The field's label, as an alternative to the `label` attribute.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages.
 */
export default class CraftSelect extends LionSelect {
  static override get styles() {
    return [...super.styles, styles];
  }

  /** Renders the control at a smaller size. */
  @property({reflect: true, type: Boolean}) small = false;

  // oxlint-disable-next-line class-methods-use-this
  override _inputGroupInputTemplate() {
    return html`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `;
  }
}

if (!customElements.get('craft-select')) {
  customElements.define('craft-select', CraftSelect);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select': CraftSelect;
    // 'craft-select-invoker': CraftSelectInvoker;
  }
}
