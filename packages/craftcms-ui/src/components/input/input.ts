import {inputStyles} from '@src/styles/form.styles';
import styles from './input.styles.js';
import {LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {HasLabel} from '@src/mixins/HasLabel';
import {formControlStyles, type SlotFactory} from '@src/mixins/FormControl';
import {NativeField} from '@src/mixins/NativeField';

/**
 * @summary A single-line text input, and the base every other `craft-input-*`
 * control extends. It brings a label and help text alongside a native
 * `<input>`, whose `value`, `input` and `change` are the component's.
 *
 * Set `label` and `help-text` as attributes for plain text, or slot them when
 * they need markup. Change `type` to get any of the native input types —
 * `craft-input-date` and `craft-input-time` are exactly that, with the type
 * fixed and a little behavior added.
 *
 * A bare input is unlabeled; either give it a `label`, or wrap it in
 * `craft-field`, which supplies the label and error handling for you.
 *
 * @slot input - The native input. Supplied by the component; you rarely touch
 *   this.
 * @slot label - The control's label, as an alternative to the `label`
 *   attribute.
 * @slot help-text - Guidance shown below the label, as an alternative to the
 *   `help-text` attribute.
 * @slot feedback - Validation messages. Set `invalid` alongside them.
 * @slot prefix - Content inside the control, before the input.
 * @slot suffix - Content inside the control, after the input, in its own
 *   segment. See `inline-suffix`.
 * @slot before - Content outside the control, before it.
 * @slot after - Content outside the control, after it.
 */
export default class CraftInput extends HasLabel(NativeField(LitElement)) {
  static override styles = [formControlStyles, inputStyles, styles];

  /** The native input's type. Leaves the control's own when unset. */
  @property({reflect: true}) type?: string;

  /** Creates the native input when none is slotted. */
  override get slots(): Record<string, SlotFactory> {
    return {
      ...super.slots,
      input: () => {
        const input = document.createElement('input');
        const value = this.getAttribute('value');

        input.type = this.type ?? 'text';
        if (value) {
          input.setAttribute('value', value);
        }

        return input;
      },
    };
  }

  override get _inputNode(): HTMLInputElement | undefined {
    return super._inputNode as HTMLInputElement | undefined;
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
   * Draws `suffix` content inside the input's border, with no divider before
   * it, and leaves a button there at its own size instead of filling the
   * segment. Suits a button that acts on the value, such as one that
   * regenerates it.
   */
  @property({reflect: true, type: Boolean, attribute: 'inline-suffix'})
  inlineSuffix = false;

  /**
   * Visually hides the control while keeping it in the DOM and form-bound, so
   * its value still submits. Useful for fields that are populated/managed
   * programmatically but must round-trip with the form.
   */
  @property({reflect: true, type: Boolean, attribute: 'hidden-input'})
  hiddenInput = false;

  override connectedCallback() {
    super.connectedCallback();
    this.syncNativeAttributes();
  }

  protected override _pushToControl(changed: PropertyValues): void {
    super._pushToControl(changed);

    if (changed.has('type') && this.type && this._inputNode) {
      this._inputNode.type = this.type;
    }
  }

  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (
      changedProperties.has('inputSize') ||
      changedProperties.has('maxlength') ||
      changedProperties.has('width') ||
      changedProperties.has('min') ||
      changedProperties.has('max') ||
      changedProperties.has('step') ||
      changedProperties.has('inputMode') ||
      changedProperties.has('autoCorrect') ||
      changedProperties.has('autoCapitalize')
    ) {
      this.syncNativeAttributes();
    }
  }

  /**
   * Gives the input an intrinsic width matching its `maxlength`, so the shrunk
   * control (see `width`) fits the expected character count. `width="full"`
   * opts out of the shrink behavior, so the intrinsic width would only blow
   * the control out of its column.
   */
  private get maxlengthSize(): number | undefined {
    return this.maxlength && this.maxlength > 0 && this.width !== 'full'
      ? this.maxlength
      : undefined;
  }

  private syncNativeAttributes() {
    const attributes = {
      size: this.inputSize ?? this.maxlengthSize,
      maxlength:
        this.maxlength && this.maxlength > 0 ? this.maxlength : undefined,
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
