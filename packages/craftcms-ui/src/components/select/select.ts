import styles from './select.styles.js';
import {LionSelect} from '@lion/ui/select.js';
import {html} from 'lit';
import '../option/option.js';
import '../icon/icon.js';
import {property} from 'lit/decorators.js';

export default class CraftSelect extends LionSelect {
  static override get styles() {
    return [...super.styles, styles];
  }

  @property({reflect: true, type: Boolean}) small = false;

  // eslint-disable-next-line class-methods-use-this
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
