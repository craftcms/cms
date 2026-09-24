import {LionRadio} from '@lion/ui/radio-group.js';
import {css} from 'lit';
import {SsrChoiceInputMixin} from '@src/mixins/SsrChoiceInputMixin';

/**
 * @summary One option within a `craft-radio-group`. A radio outside a group
 * has nothing to be exclusive against, so it is always used in one.
 *
 * Built on Lion's radio, and like `craft-checkbox` it keeps the `checked`,
 * `disabled`, and `name` of a server-rendered input rather than resetting them
 * on upgrade.
 *
 * @slot label - The option's label, as an alternative to the `label`
 *   attribute.
 * @slot help-text - Guidance shown below the label.
 */
export default class CraftRadio extends SsrChoiceInputMixin(LionRadio) {
  static override get styles() {
    return [
      ...super.styles,
      css`
        /* same as checkbox, potentially consolidate */
        :host {
          gap: var(--c-spacing-sm);
        }
      `,
    ];
  }
}

if (!customElements.get('craft-radio')) {
  customElements.define('craft-radio', CraftRadio);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-radio': CraftRadio;
  }
}
