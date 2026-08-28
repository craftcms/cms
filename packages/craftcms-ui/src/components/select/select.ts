import styles from './select.styles.js';
import {LionSelect} from '@lion/ui/select.js';
import {html, type PropertyValues} from 'lit';
import '../option/option.js';
import '../icon/icon.js';
import {property} from 'lit/decorators.js';

export default class CraftSelect extends LionSelect {
  static override get styles() {
    return [...super.styles, styles];
  }

  @property({reflect: true, type: Boolean}) small = false;

  /**
   * Renders the label beside the control instead of stacked above it.
   * No-op when combined with `label-sr-only`, or when help text/instructions
   * are present (falls back to the normal stacked layout automatically).
   */
  @property({type: String, reflect: true, attribute: 'label-position'})
  labelPosition?: 'top' | 'start';

  protected override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);
    this.toggleAttribute('has-help-text', !!this.helpText);
  }

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
