import {LionRadio} from '@lion/ui/radio-group.js';
import {css} from 'lit';
import {SsrChoiceInputMixin} from '@src/mixins/SsrChoiceInputMixin';

export default class CraftRadio extends SsrChoiceInputMixin(LionRadio) {
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
