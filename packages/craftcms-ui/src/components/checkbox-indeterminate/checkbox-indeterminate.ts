import {LionCheckboxIndeterminate} from '@lion/ui/checkbox-group.js';
import {css, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {choiceInputStyles} from '@src/styles/form.styles';
import {SsrChoiceInputMixin} from '@src/mixins/SsrChoiceInputMixin';

/**
 * @summary An "All" checkbox governing the checkboxes nested inside it.
 *
 * Beyond Lion's select-all, this can store "all of them" as a single token
 * (`*`) for backwards compatibilty. In `single-value` mode the governed boxes
 * still render checked — the group reads as fully selected — but they stop
 * carrying a `name`, so only this checkbox's own value posts.
 *
 * Parking the name is deliberate. `disabled` would suppress the post too, but
 * Lion propagates a change to its children with
 * `_subCheckboxes.filter(cb => !cb.disabled)` — so disabling them is precisely
 * what stops "All" from clearing them again, leaving a toggle that takes
 * several clicks to come back around.
 */
export default class CraftCheckboxIndeterminate extends SsrChoiceInputMixin(
  LionCheckboxIndeterminate
) {
  /**
   * `single-value` (default): checking this posts its own value alone.
   * `each-value`: a plain select-all — every governed box posts its own value.
   */
  @property({reflect: true, attribute: 'all-mode'}) allMode:
    | 'single-value'
    | 'each-value' = 'single-value';

  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (changedProperties.has('checked') || changedProperties.has('allMode')) {
      this.#syncGovernedNames();
    }
  }

  /**
   * Suppresses the governed boxes from posting while this one speaks for them,
   * by parking their `name` — see the note on `disabled` above.
   */
  #syncGovernedNames() {
    const suppressed = this.allMode === 'single-value' && this.checked;

    for (const checkbox of this._subCheckboxes ?? []) {
      const input = checkbox.querySelector<HTMLInputElement>(
        ':scope > input[slot="input"]'
      );

      if (!input) {
        continue;
      }

      if (suppressed) {
        if (input.name) {
          input.dataset.governedName = input.name;
          input.removeAttribute('name');
        }
      } else if (input.dataset.governedName) {
        input.name = input.dataset.governedName;
        delete input.dataset.governedName;
      }
    }
  }

  static override get styles() {
    return [
      ...LionCheckboxIndeterminate.styles,
      choiceInputStyles,
      css`
        :host {
          display: flex;
          align-items: center;
          gap: 0 var(--c-spacing-md);
        }

        ::slotted(label) {
          font-weight: bold;
        }

        ::slotted(*) {
          padding-left: 0;
        }
      `,
    ];
  }
}

if (!customElements.get('craft-checkbox-indeterminate')) {
  customElements.define(
    'craft-checkbox-indeterminate',
    CraftCheckboxIndeterminate
  );
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-checkbox-indeterminate': CraftCheckboxIndeterminate;
  }
}
