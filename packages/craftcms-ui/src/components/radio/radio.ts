import {LionRadio} from '@lion/ui/radio-group.js';
import {css} from 'lit';

export default class CraftRadio extends LionRadio {
  static override get styles() {
    return [
      ...super.styles,
      css`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `,
    ];
  }
}

if (!customElements.get('craft-radio')) {
  customElements.define('craft-radio', CraftRadio);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-radio': CraftRadio;
  }
}
