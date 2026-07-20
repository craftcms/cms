import {LionCheckboxGroup} from '@lion/ui/checkbox-group.js';
import {css} from 'lit';

export default class CraftCheckboxGroup extends LionCheckboxGroup {
  static override get styles() {
    return [
      ...LionCheckboxGroup.styles,
      css`
        .input-group {
          display: grid;
          gap: var(--c-spacing-sm);
        }

        .form-field__group-two {
          margin-top: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font-weight: bold;
        }
      `,
    ];
  }
}

if (!customElements.get('craft-checkbox-group')) {
  customElements.define('craft-checkbox-group', CraftCheckboxGroup);
}
