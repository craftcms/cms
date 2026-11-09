import {LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {FormAssociated} from '@src/mixins/FormAssociated';
import {FormControl, formControlStyles} from '@src/mixins/FormControl';
import {HasLabel} from '@src/mixins/HasLabel';
import {HostAriaReferences} from '@src/mixins/HostAriaReferences';
import {emitChange, emitInput} from '@src/utilities/form-events';
import type CraftSlidePickerSlider from './slide-picker-slider.js';
import './slide-picker-slider.js';
import styles from './slide-picker.styles.js';

/**
 * @summary Segmented slider control for selecting numeric values in fixed steps.
 * @since 1.0
 *
 * The slider is a `craft-slide-picker-slider` in its `input` slot, so the
 * label, help text and feedback — its own, or a surrounding
 * `craft-field`'s — name and describe it the way they do any other control. A
 * hidden input carries the value, so a picker with a `name` posts like any
 * other field, including through `$form.serialize()` and Garnish's
 * `getPostData()`, which only collect native inputs.
 *
 * Outside a field, `labelled-by` and `described-by` on the picker name and
 * describe the slider after elements elsewhere, such as a table column
 * heading.
 *
 * @attr value - The selected value.
 * @attr labelled-by - Ids of elements elsewhere that label the control, such
 *   as a table column heading.
 * @attr described-by - Ids of elements elsewhere that describe the control.
 *
 * @slot label - The picker's label, as an alternative to the `label`
 *   attribute.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages.
 *
 * @fires input - Emitted on every alteration to the value.
 * @fires change - Emitted when an alteration to the value is committed by the
 * user. Every alteration is a commit here, so it follows each `input`.
 *
 * @cssproperty --c-slide-picker-segment-fill - Fill of the segments past the
 * value. Defaults to `--c-color-neutral-fill-quiet`.
 * @cssproperty --c-slide-picker-segment-active-fill - Fill of the segments up
 * to the value. Defaults to `--c-color-neutral-fill-loud` mixed 25% toward
 * `--c-color-neutral-fill-normal`.
 * @cssproperty --c-slide-picker-segment-hover-fill - Fill of the segments up
 * to the hovered one, previewing a value. Defaults to
 * `--c-color-neutral-fill-normal`.
 */
export default class CraftSlidePicker extends HostAriaReferences(
  FormAssociated(HasLabel(FormControl(LitElement)))
) {
  static override styles = [formControlStyles, styles];

  /** The selected value. */
  @property({type: Number}) value = 0;
  /** Lowest selectable value. */
  @property({type: Number}) min = 0;
  /** Highest selectable value. */
  @property({type: Number}) max = 100;
  /** Spacing between selectable values. */
  @property({type: Number}) step = 10;
  /**
   * Unit announced after the value — `columns`, `px`, `%`. A unit that starts
   * with a letter is separated from the value by a space; a symbol is not.
   * Without one a bare number is all a screen reader gets.
   */
  @property({attribute: 'value-unit'}) valueUnit = '';
  /**
   * Formats the announced value, for when a number and a unit are not enough.
   * A property rather than an attribute, so bind it with `.valueLabel`.
   */
  @property({attribute: false}) valueLabel?: (value: number) => string;

  #input?: HTMLInputElement;

  /** The value a form reset goes back to, read on first connect. */
  #defaultValue?: number;

  /** Adds the slider to the slot map. */
  override get slots() {
    return {
      ...super.slots,
      input: () => document.createElement('craft-slide-picker-slider'),
    };
  }

  get #slider(): CraftSlidePickerSlider | undefined {
    return this._inputNode as CraftSlidePickerSlider | undefined;
  }

  override connectedCallback() {
    super.connectedCallback();
    this.#defaultValue ??= this.#normalize(this.value);
    this.#slider?.addEventListener('input', this.#onSliderInput);
  }

  override disconnectedCallback() {
    this.#slider?.removeEventListener('input', this.#onSliderInput);
    super.disconnectedCallback();
  }

  // The hidden input posts the value, so `ElementInternals` posts nothing.
  // oxlint-disable-next-line class-methods-use-this
  override _formValue(): string | null {
    return null;
  }

  /** Restores the starting value when the owning form is reset. */
  formResetCallback() {
    this.value = this.#defaultValue ?? this.min;
  }

  // Every step is a commit, so `change` follows `input` immediately — what a
  // radio group does, not what a range slider does.
  #onSliderInput = (event: Event): void => {
    event.stopPropagation();
    this.value = Number((event.target as CraftSlidePickerSlider).value);
    this.#syncInput();
    emitInput(this);
    emitChange(this);
  };

  #normalize(value: unknown): number {
    const {min, max, step} = this;
    const number = Number.isFinite(value) ? (value as number) : min;
    const clamped = Math.min(Math.max(number, min), max);
    const snapped = min + Math.round((clamped - min) / step) * step;
    return Math.min(Math.max(snapped, min), max);
  }

  #valueText(value: number): string {
    if (this.valueLabel) {
      return this.valueLabel(value);
    }
    const unit = this.valueUnit.trim();
    // A word gets a space ("3 columns"); a symbol doesn't ("100%").
    const separator = /^\p{L}/u.test(unit) ? ' ' : '';
    return unit ? `${value}${separator}${unit}` : `${value}`;
  }

  protected override willUpdate(changed: PropertyValues<this>) {
    super.willUpdate(changed);

    const normalized = this.#normalize(this.value);
    if (normalized !== this.value) {
      this.value = normalized;
    }
  }

  override updated(changed: PropertyValues<this>) {
    super.updated(changed);

    const slider = this.#slider;
    if (slider) {
      slider.min = this.min;
      slider.max = this.max;
      slider.step = this.step;
      slider.value = String(this.value);
      slider.readOnly = this.readOnly;
      slider.disabled = this.disabled;
      slider.valueText = (value) => this.#valueText(value);
    }

    this.#syncInput();
  }

  /** Posts the value through a hidden input while the picker has a name. */
  #syncInput(): void {
    if (!this.name) {
      this.#input?.remove();
      return;
    }

    if (!this.#input) {
      this.#input = document.createElement('input');
      this.#input.type = 'hidden';
      // A slot the shadow root doesn't render.
      this.#input.slot = 'form-value';
    }

    if (this.#input.parentNode !== this) {
      this.append(this.#input);
    }

    this.#input.name = this.name;
    this.#input.value = String(this.value);
    this.#input.disabled = this.disabled;
  }

  /** Focuses the slider, as a `craft-field` does when its label is clicked. */
  override focus(options?: FocusOptions): void {
    this.#slider?.focus(options);
  }

  /** Clicking the label focuses the slider, as it would a native input. */
  protected override _onLabelClick() {
    if (!this.disabled) {
      this.#slider?.focus();
    }
  }
}

if (!customElements.get('craft-slide-picker')) {
  customElements.define('craft-slide-picker', CraftSlidePicker);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-slide-picker': CraftSlidePicker;
  }
}
