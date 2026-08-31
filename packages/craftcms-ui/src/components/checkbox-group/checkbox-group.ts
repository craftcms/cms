import {LionCheckboxGroup} from '@lion/ui/checkbox-group.js';
import {css, type PropertyValues} from 'lit';
import {baseFieldStyles} from '@src/styles/form.styles';

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
export default class CraftCheckboxGroup extends LionCheckboxGroup {
  private __ssrNameAdopted = false;

  /**
   * Adopts the group name from server-rendered checkbox inputs. Lion syncs
   * each registered child's name to the group's (`name || ''`), so a
   * nameless group would strip the SSR'd names and break native posting.
   * Runs before `super.connectedCallback()` so the name is set before any
   * child registers; `willUpdate` is the fallback for streaming parsing,
   * where the element can connect before its children exist.
   */
  override connectedCallback() {
    this.__adoptSlottedName();
    super.connectedCallback();
  }

  protected override willUpdate(changedProperties: PropertyValues) {
    this.__adoptSlottedName();
    super.willUpdate(changedProperties);
  }

  private __adoptSlottedName() {
    if (this.__ssrNameAdopted || this.name) {
      return;
    }

    const input = this.querySelector<HTMLInputElement>(
      'input[type="checkbox"][name]'
    );

    if (!input) {
      // Leave the flag unset so a later pass can still adopt once children
      // have been parsed.
      return;
    }

    this.__ssrNameAdopted = true;
    this.name = input.name;
  }

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
