import {LionCombobox} from '@lion/ui/combobox.js';
import {html} from 'lit';
import styles from './combobox.styles.js';
import type CraftOption from '../option/option.js';
import '../option/option.js';
import '../icon/icon.js';

export default class CraftCombobox extends LionCombobox {
  static override get styles() {
    return [...super.styles, styles];
  }

  constructor() {
    super();
    // Configure validators on construction
    this.defaultValidators = [];
  }

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

  /**
   * Override to use the option's text content instead of choiceValue
   */
  override _getTextboxValueFromOption(option: CraftOption) {
    if (option) {
      // Return the option's text content instead of choiceValue
      return option.textContent?.trim() || '';
    }

    // @ts-ignore Lion's code handles `null` but the types don't account for it
    return super._getTextboxValueFromOption(option);
  }
}

if (!customElements.get('craft-combobox')) {
  customElements.define('craft-combobox', CraftCombobox);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-combobox': CraftCombobox;
  }
}
