import {css, html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {ChoiceInput, choiceInputStyles} from '../../mixins/ChoiceInput.js';

/**
 * @summary A single checkbox, for a choice that stands on its own or one of
 * several in a `craft-checkbox-group`.
 *
 * The native checkbox in its light DOM is what posts and what holds the state,
 * so `checked` reads it, and its `input` and `change` events are the
 * checkbox's. A checkbox that arrives from the server with its input already
 * in place keeps that input's `checked`, `disabled`, `name` and `value`.
 *
 * @slot label - The checkbox's label, as an alternative to the `label`
 *   attribute.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages, shown below the help text.
 *
 * @fires {Event} input - When the user checks or unchecks it.
 * @fires {Event} change - When the user checks or unchecks it.
 */
export default class CraftCheckbox extends ChoiceInput(LitElement) {
  /**
   * Shows the checkbox as partly checked, for a "select all" whose items are
   * only some of them checked. Display only: `checked` is unaffected.
   */
  @property({type: Boolean, reflect: true}) indeterminate = false;

  /** The native checkbox, unless the markup brings its own. */
  override get slots() {
    return {
      ...super.slots,
      input: () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        return input;
      },
    };
  }

  #indeterminateInput?: HTMLInputElement;

  constructor() {
    super();
    // A click clears the input's indeterminate state, so the host follows.
    this.addEventListener('change', (event) => {
      if (event.target === this._control && this.indeterminate) {
        this.indeterminate = (event.target as HTMLInputElement).indeterminate;
      }
    });
  }

  override updated(changed: PropertyValues) {
    super.updated(changed);

    const input = this._control;
    if (
      input instanceof HTMLInputElement &&
      (changed.has('indeterminate') || input !== this.#indeterminateInput)
    ) {
      input.indeterminate = this.indeterminate;
      this.#indeterminateInput = input;
    }
  }

  override render() {
    return html`${super.render()} ${this._feedbackTemplate()}`;
  }

  static override styles = [
    choiceInputStyles,
    css`
      :host(:not([label-sr-only])) {
        --_gap-x: var(--gap-x, var(--c-spacing-md));
        display: grid;
        align-items: center;
        gap: 0 var(--_gap-x);
        grid-template-areas: 'input label' '. help-text' '. feedback';
        grid-template-columns: auto 1fr;
        grid-template-rows: repeat(3, auto);
      }

      ::slotted(label) {
        font: inherit;
        grid-area: label;
      }

      .choice-field__help-text {
        font-size: 1em;
        color: var(--c-text-quiet);
        grid-area: help-text;
      }

      .form-field__feedback {
        flex-basis: 100%;
        grid-area: feedback;
      }
    `,
  ];
}

if (!customElements.get('craft-checkbox')) {
  customElements.define('craft-checkbox', CraftCheckbox);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-checkbox': CraftCheckbox;
  }
}
