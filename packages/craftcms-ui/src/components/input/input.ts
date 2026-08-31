import {LionInput} from '@lion/ui/input.js';
import {inputStyles} from '@src/styles/form.styles';
import styles from './input.styles.js';
import type {PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * @summary A single-line text input, and the base every other `craft-input-*`
 * control extends. Built on Lion's input, so it brings a label, help text,
 * validation, and a `modelValue` alongside the native field.
 *
 * Set `label` and `help-text` as attributes for plain text, or slot them when
 * they need markup. Change `type` to get any of the native input types —
 * `craft-input-date` and `craft-input-time` are exactly that, with the type
 * fixed and a little behaviour added.
 *
 * A bare input is unlabelled; either give it a `label`, or wrap it in
 * `craft-field`, which supplies the label and error handling for you.
 *
 * @slot input - The native input. Supplied by the component; you rarely touch
 *   this.
 * @slot label - The control's label, as an alternative to the `label`
 *   attribute.
 * @slot help-text - Guidance shown below the label, as an alternative to the
 *   `help-text` attribute.
 * @slot feedback - Validation messages. Lion fills this from the control's
 *   validators.
 * @slot prefix - Content inside the control, before the input.
 * @slot suffix - Content inside the control, after the input.
 * @slot before - Content outside the control, before it.
 * @slot after - Content outside the control, after it.
 */
export default class CraftInput extends LionInput {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }

  /** Maximum number of characters, applied to the native input's `maxLength`. */
  @property({type: Number, reflect: true}) maxlength?: number;

  /** Control size. */
  @property({type: String, reflect: true}) size?: 'small' | 'medium' | 'large' =
    'medium';

  /** Native input size in characters. */
  @property({type: Number, attribute: false}) inputSize?: number;

  /** Native minimum value. */
  @property({attribute: false}) min?: string | number;

  /** Native maximum value. */
  @property({attribute: false}) max?: string | number;

  /** Native stepping interval. */
  @property({attribute: false}) step?: string | number;

  /** Native virtual keyboard hint. */
  @property({attribute: false}) override inputMode = '';

  /** Whether the native input allows browser autocorrection. */
  @property({attribute: false}) autoCorrect = true;

  /** Whether the native input allows automatic capitalization. */
  @property({attribute: false}) autoCapitalize = true;

  /** Renders the input at a smaller size. */
  @property({reflect: true, type: Boolean}) small = false;

  /**
   * Overrides the inferred width behavior. By default the control spans its
   * column, unless `maxlength` is set — which shrinks the control to the
   * expected character width. `full` spans the column despite a `maxlength`;
   * `auto` shrinks to the input's intrinsic width without one.
   */
  @property({type: String, reflect: true}) width?: 'full' | 'auto';

  /** Center-aligns the input text. */
  @property({reflect: true, type: Boolean}) center = false;

  /** Renders the input value in a monospace font. */
  @property({reflect: true, type: Boolean}) monospace = false;

  /**
   * Visually hides the control while keeping it in the DOM and form-bound, so
   * its value still submits. Useful for fields that are populated/managed
   * programmatically but must round-trip with the form.
   */
  @property({reflect: true, type: Boolean, attribute: 'hidden-input'})
  hiddenInput = false;

  override connectedCallback() {
    super.connectedCallback();

    if (this._inputNode && this.maxlength && this.maxlength > 0) {
      this._inputNode.maxLength = this.maxlength;
      // Give the input a matching intrinsic width, so the shrunk control
      // (see `width`) fits the expected character count. `width="full"`
      // opts out of the shrink behavior, so the intrinsic width would only
      // blow the control out of its column.
      if (this.width !== 'full') {
        this._inputNode.size = this.maxlength;
      }
    }

    this.syncNativeAttributes();
    this.syncAriaInvalid();
  }

  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (
      changedProperties.has('inputSize') ||
      changedProperties.has('min') ||
      changedProperties.has('max') ||
      changedProperties.has('step') ||
      changedProperties.has('inputMode') ||
      changedProperties.has('autoCorrect') ||
      changedProperties.has('autoCapitalize')
    ) {
      this.syncNativeAttributes();
    }

    this.syncAriaInvalid();
  }

  private syncAriaInvalid() {
    const ariaInvalid = this.getAttribute('aria-invalid');

    if (ariaInvalid !== null) {
      this._inputNode?.setAttribute('aria-invalid', ariaInvalid);
    }
  }

  private syncNativeAttributes() {
    const attributes = {
      size: this.inputSize,
      min: this.min,
      max: this.max,
      step: this.step,
      inputmode: this.inputMode,
      autocorrect: this.autoCorrect ? undefined : 'off',
      autocapitalize: this.autoCapitalize ? undefined : 'none',
    };

    for (const [attribute, value] of Object.entries(attributes)) {
      if (value === undefined) {
        this._inputNode?.removeAttribute(attribute);

        continue;
      }

      this._inputNode?.setAttribute(attribute, String(value));
    }
  }
}

if (!customElements.get('craft-input')) {
  customElements.define('craft-input', CraftInput);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input': CraftInput;
  }
}
