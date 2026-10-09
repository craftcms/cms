import CraftInput from '../input/input.js';

/**
 * @summary A date input. `craft-input` with its `type` fixed to `date`, so it
 * carries the same label, help text, validation, and slots, and renders the
 * browser's own date picker.
 *
 * Values are `YYYY-MM-DD`, and `min` and `max` take the same form. To edit a
 * date and a time together, use `craft-input-date-time`, which pairs this with
 * `craft-input-time` and submits them as one value.
 */
export default class CraftInputDate extends CraftInput {
  /**
   * Fixed to `date`, which is the point of the component — it renders the
   * browser's date picker rather than a text field.
   */
  override type = 'date';

  constructor() {
    super();
  }
}

if (!customElements.get('craft-input-date')) {
  customElements.define('craft-input-date', CraftInputDate);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-date': CraftInputDate;
  }
}
