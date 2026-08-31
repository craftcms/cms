import {LionRadioGroup} from '@lion/ui/radio-group.js';
import {inputStyles} from '@src/styles/form.styles';
import {css, type PropertyValues} from 'lit';

/**
 * @summary A group of radios sharing one name, for a choice where exactly one
 * option may be selected.
 *
 * The group owns the name and the value, so its `modelValue` is the value of
 * whichever radio is checked. Use `craft-checkbox-group` when more than one
 * option may be chosen.
 *
 * A group rendered by the server adopts the name already on its inputs, so the
 * markup keeps posting the way it did before the component upgraded.
 *
 * @slot - The `craft-radio`s in the group.
 * @slot label - The group's label.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages for the group as a whole.
 */
export default class CraftRadioGroup extends LionRadioGroup {
  private __ssrNameAdopted = false;

  /**
   * Adopts the group name from server-rendered radio inputs. Lion syncs
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
      'input[type="radio"][name]'
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
