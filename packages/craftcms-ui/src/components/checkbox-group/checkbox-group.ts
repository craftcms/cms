import {css, LitElement} from 'lit';
import {baseFieldStyles} from '@src/styles/form.styles';
import {ChoiceGroup} from '@src/mixins/ChoiceGroup';
import {formControlStyles} from '@src/mixins/FormControl';
import {HasLabel} from '@src/mixins/HasLabel';

/**
 * @summary A group of checkboxes sharing one name, for a choice where any
 * number of options can be selected.
 *
 * The group owns the name, and its `value` is the array of values that are
 * checked. Use `craft-radio-group` when exactly one option may be chosen.
 *
 * A group rendered by the server adopts the name already on its inputs, so the
 * markup keeps posting the way it did before the component upgraded.
 *
 * @slot - The `craft-checkbox`es in the group.
 * @slot label - The group's label.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages for the group as a whole.
 *
 * @fires {Event} input - When the user checks or unchecks an option.
 * @fires {Event} change - When the user checks or unchecks an option.
 */
export default class CraftCheckboxGroup extends HasLabel(
  ChoiceGroup(LitElement, {choice: 'craft-checkbox', multiple: true})
) {
  /** The values of the checked checkboxes, in order. Setting it checks those and clears the rest. */
  declare value: string[];

  static override styles = [
    formControlStyles,
    css`
      ${baseFieldStyles}

      .input-group {
        display: grid;
        gap: var(--c-spacing-sm);
      }

      ::slotted(label) {
        font-weight: bold;
      }
    `,
  ];
}

if (!customElements.get('craft-checkbox-group')) {
  customElements.define('craft-checkbox-group', CraftCheckboxGroup);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-checkbox-group': CraftCheckboxGroup;
  }
}
