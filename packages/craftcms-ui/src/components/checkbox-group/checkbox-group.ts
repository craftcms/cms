import {LionCheckboxGroup} from '@lion/ui/checkbox-group.js';
import {css} from 'lit';
import {baseFieldStyles} from '@src/styles/form.styles';
import {SsrChoiceGroupMixin} from '@src/mixins/SsrChoiceGroupMixin';

/**
 * @summary A group of checkboxes sharing one name, for a choice where any
 * number of options can be selected.
 *
 * The group owns the name and the collected value, so its `modelValue` is the
 * array of what is checked. Use `craft-radio-group` when exactly one option
 * may be chosen.
 *
 * A group rendered by the server adopts the name already on its inputs, so the
 * markup keeps posting the way it did before the component upgraded.
 *
 * @slot - The `craft-checkbox`es in the group.
 * @slot label - The group's label.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages for the group as a whole.
 */
export default class CraftCheckboxGroup extends SsrChoiceGroupMixin(
  LionCheckboxGroup,
  'checkbox'
) {
  static override get styles() {
    return [
      ...LionCheckboxGroup.styles,
      css`
        ${baseFieldStyles}

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

declare global {
  interface HTMLElementTagNameMap {
    'craft-checkbox-group': CraftCheckboxGroup;
  }
}
