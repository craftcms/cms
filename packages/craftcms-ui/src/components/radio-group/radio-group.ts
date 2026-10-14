import {LionRadioGroup} from '@lion/ui/radio-group.js';
import {inputStyles} from '@src/styles/form.styles';
import {SsrChoiceGroupMixin} from '@src/mixins/SsrChoiceGroupMixin';
import {css, type PropertyValues} from 'lit';
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
 * Set `buttons` to show the options as a row of joined buttons, like a
 * selection-mode `craft-button-group`. They're still radios, so they're
 * announced as radios and the arrow keys move the selection. An option whose
 * label has no text, such as a labeled `craft-icon`, gets a square button.
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

  /**
   * Shows the options as a row of joined buttons rather than a stacked list.
   */
  @property({type: Boolean, reflect: true}) buttons = false;

  /** Registers a radio with the group. Called by Lion as radios connect. */
  override addFormElement(child: unknown, indexToInsertAt?: number) {
    super.addFormElement(
      child as Parameters<LionRadioGroup['addFormElement']>[0],
      indexToInsertAt as number
    );
    this._syncButtons();
  }

  /** Unregisters a radio from the group. Called by Lion as radios disconnect. */
  override removeFormElement(child: unknown) {
    super.removeFormElement(
      child as Parameters<LionRadioGroup['removeFormElement']>[0]
    );
    this._syncButtons();
  }

  override updated(changed: PropertyValues) {
    super.updated(changed);
    if (changed.has('buttons')) {
      this._syncButtons();
    }
  }

  /**
   * Tells each radio whether to draw itself as a button, and which ones sit
   * at the ends of the row, so only those get rounded outer corners.
   */
  private _syncButtons() {
    const radios = Array.from(this.formElements) as HTMLElement[];

    radios.forEach((radio, index) => {
      radio.toggleAttribute('data-button', this.buttons);
      radio.toggleAttribute('data-button-start', this.buttons && index === 0);
      radio.toggleAttribute(
        'data-button-end',
        this.buttons && index === radios.length - 1
      );
    });
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

        :host([buttons]) .input-group {
          display: flex;
          flex-wrap: wrap;
          width: fit-content;
          gap: 0;
        }

        :host([buttons]) ::slotted(:not(craft-radio)) {
          display: contents;
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
