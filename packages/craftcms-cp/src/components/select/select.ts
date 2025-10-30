import styles from './select.styles.js';
import {LionSelectInvoker, LionSelectRich} from '@lion/ui/select-rich.js';
import {css, html} from 'lit';
import type CraftIcon from '../icon/icon.js';
import {baseInputStyles} from '../../styles/form.styles.js';
import '../option/option.js';
import '../icon/icon.js';

export default class CraftSelect extends LionSelectRich {
  static override get styles() {
    return [...super.styles, styles];
  }

  /** @type {SlotsMap} */
  override get slots() {
    return {
      ...super.slots,
      invoker: () => html`<craft-select-invoker></craft-select-invoker>`,
    };
  }
}

export class CraftSelectInvoker extends LionSelectInvoker {
  static override get styles() {
    return [
      ...super.styles,
      css`
        :host {
          ${baseInputStyles}
          box-shadow: var(--c-select-shadow);
        }
      `,
    ];
  }

  override get slots() {
    return {
      ...super.slots,
      after: () => {
        const icon = document.createElement('craft-icon') as CraftIcon;
        icon.style.fontSize = '0.8em';
        icon.name = 'chevron-down';
        return icon;
      },
    };
  }
}

if (!customElements.get('craft-select')) {
  customElements.define('craft-select', CraftSelect);
}

if (!customElements.get('craft-select-invoker')) {
  customElements.define('craft-select-invoker', CraftSelectInvoker);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select': CraftSelect;
    'craft-select-invoker': CraftSelectInvoker;
  }
}
