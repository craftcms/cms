import {LionRadioGroup} from '@lion/ui/radio-group.js';
import {inputStyles} from '@src/styles/form.styles';
import {SsrChoiceGroupMixin} from '@src/mixins/SsrChoiceGroupMixin';
import {css} from 'lit';
import {property} from 'lit/decorators.js';
import {HasLabel} from '@src/mixins/HasLabel';

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
export default class CraftRadioGroup extends HasLabel(
  SsrChoiceGroupMixin(LionRadioGroup, 'radio')
) {
  /**
   * Lays the options out as a row of tiles rather than a stacked list, for
   * groups whose options carry a thumbnail.
   */
  @property({type: Boolean, reflect: true}) thumbnails = false;

  static override get styles() {
    return [
      ...super.styles,
      inputStyles,
      css`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }

        /*
         * Options illustrated with a thumbnail read as a row of tiles rather
         * than a stacked list — the layout Craft 5 hand-rolled for View Mode.
         */
        :host([thumbnails]) .input-group {
          grid-auto-flow: column;
          justify-content: start;
          align-items: start;
          gap: var(--c-spacing-lg);
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
