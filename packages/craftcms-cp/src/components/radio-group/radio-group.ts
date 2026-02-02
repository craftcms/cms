import {LionRadioGroup} from '@lion/ui/radio-group.js';
import {inputStyles} from '@src/styles/form.styles';
import {css} from 'lit';

export default class CraftRadioGroup extends LionRadioGroup {
  static override get styles() {
    return [
      ...super.styles,
      inputStyles,
      css`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }
      `,
    ];
  }
}

if (!customElements.get('craft-radio-group')) {
  customElements.define('craft-radio-group', CraftRadioGroup);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-radio-group': CraftRadioGroup;
  }
}
