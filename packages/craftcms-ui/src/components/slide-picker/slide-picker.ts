import {LionField} from '@lion/ui/form-core.js';
import {html, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {HasLabel} from '@src/mixins/HasLabel';
import {HostAriaReferences} from '@src/mixins/HostAriaReferences';
import {baseFieldStyles} from '@src/styles/form.styles';
import {emitChange, emitInput} from '@src/utilities/form-events';
import type CraftSlidePickerSlider from './slide-picker-slider.js';
import './slide-picker-slider.js';
import styles from './slide-picker.styles.js';

/**
 * @summary Segmented slider control for selecting numeric values in fixed steps.
 * @since 1.0
 *
 * A Lion field: the slider is a `craft-slide-picker-slider` in its `input`
 * slot, so the label, help text and feedback — its own, or a surrounding
 * `craft-field`'s — name and describe it the way they do any other control. A
 * hidden input carries the value, so a picker with a `name` posts like any
 * other field, including through `$form.serialize()` and Garnish's
 * `getPostData()`, which only collect native inputs.
 *
 * Outside a field, `labelled-by` and `described-by` on the picker name and
 * describe the slider after elements elsewhere, such as a table column
 * heading.
 *
 * @attr model-value - The selected value.
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
 * @fires model-value-changed - The value changed.
 *
 * `model-value-changed` is Lion's own protocol name, not one this package
 * chose, and it is kept because Lion's form system dispatches and listens for
 * it. Prefer the native events above: they are the contract this package
 * supports, and they carry the component as `event.target`.
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
  HasLabel(LionField)
) {
  /**
   * Makes the browser treat the picker as a form control, so a form reset
   * that isn't canceled calls `formResetCallback()`. The hidden input posts
   * the value.
   */
  static formAssociated = true;

  static override get styles() {
    return [...super.styles, baseFieldStyles, styles];
  }

  static override get observedAttributes() {
    return [...super.observedAttributes, 'model-value'];
  }

  /**
   * Visually hides the label, keeping it available to screen readers.
   * `FormControlMixin` declares this property (`label-sr-only`), but its
   * types leave it out.
   */
  declare labelSrOnly: boolean;

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

  constructor() {
    super();
    // Every step is a commit, so `change` follows `input` immediately — what a
    // radio group does, not what a range slider does.
    this.addEventListener('model-value-changed', (event) => {
      if (
        event.target === this &&
        (event as CustomEvent).detail?.isTriggeredByUser
      ) {
        emitInput(this);
        emitChange(this);
      }
    });
  }

  /**
   * Adds the slider to Lion's slot map, and the hidden input that posts the
   * value when the picker has a `name`.
   */
  override get slots() {
    return {
      ...super.slots,
      input: () => document.createElement('craft-slide-picker-slider'),
      'hidden-input': () => ({
        template: html`${this.name
          ? html`<input
              type="hidden"
              name=${this.name}
              .value=${String(this.modelValue)}
              ?disabled=${this.disabled}
            />`
          : nothing}`,
      }),
    };
  }

  get #slider(): CraftSlidePickerSlider {
    return this._inputNode as unknown as CraftSlidePickerSlider;
  }

  override attributeChangedCallback(
    name: string,
    old: string | null,
    value: string | null
  ) {
    if (name === 'model-value') {
      this.modelValue = value === null ? '' : Number(value);
      return;
    }
    super.attributeChangedCallback(name, old, value);
  }

  /** Restores the starting value when the owning form is reset. */
  formResetCallback() {
    this.reset();
  }

  /** Reads the slider's string value as a number. */
  override parser(value: string): number {
    return Number(value);
  }

  /** Writes the number back to the slider as a string. */
  override formatter(value: unknown): string {
    return typeof value === 'number' ? String(value) : '';
  }

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

    const normalized = this.#normalize(this.modelValue);
    if (normalized !== this.modelValue) {
      this.modelValue = normalized;
    }
  }

  override updated(changed: PropertyValues<this>) {
    super.updated(changed);

    const slider = this.#slider;
    slider.min = this.min;
    slider.max = this.max;
    slider.step = this.step;
    slider.readOnly = this.readOnly;
    slider.disabled = this.disabled;
    slider.valueText = (value) => this.#valueText(value);
  }

  /** Clicking the label focuses the slider, as it would a native input. */
  protected _onLabelClick() {
    if (!this.disabled) {
      this.#slider.focus();
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
