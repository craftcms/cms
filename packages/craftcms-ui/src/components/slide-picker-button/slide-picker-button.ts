import {html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import {FormAssociated, type FormValue} from '@src/mixins/FormAssociated';
import {emitChange, emitInput} from '@src/utilities/form-events';
import type CraftButton from '../button/button.js';
import type CraftSlidePicker from '../slide-picker/slide-picker.js';
import styles from './slide-picker-button.styles.js';
import '../button/button.js';
import '../popover/popover.js';
import '../slide-picker/slide-picker.js';
import '../tooltip/tooltip.js';

/**
 * @summary A small button that shows a value as a static copy of a slide
 * picker's segments, and opens a full-size `craft-slide-picker` in a popover
 * to change it.
 *
 * For a slide picker that sits in a dense row, such as a widget's column
 * count or a field layout element's width, where full-size segments don't
 * fit. The button is the target, so the segments drawn in it can stay small.
 *
 * The button shows no text, so its tooltip, the value, is its visible label.
 * Its name is that value followed by `item-label`, the name of the item it
 * sizes, such as "3 columns Recent Entries". `slider-label` is shown only in
 * the popover, below its slide picker, and names that slide picker.
 *
 * @attr name - Field name. The button posts its value under it, like any
 * other input.
 * @attr disabled - Stops the button opening the popover, and leaves the value
 * out of form submission.
 *
 * @fires input - Emitted when the value is changed in the popover.
 * @fires change - Emitted when the value is changed in the popover. Every
 * change is a commit, so it follows each `input`.
 *
 * @cssproperty --c-slide-picker-segment-fill - Fill of the segments past the
 * value, in the button and the popover. Defaults to
 * `--c-color-neutral-fill-quiet` in the button and `transparent` in the
 * popover.
 * @cssproperty --c-slide-picker-segment-active-fill - Fill of the segments up
 * to the value, in the button and the popover. Defaults to a dark neutral in
 * the button and `--c-color-accent-fill-loud` in the popover. Set it only
 * with a fill that holds 3:1 against the surface.
 */
export default class CraftSlidePickerButton extends FormAssociated(LitElement) {
  static override styles = styles;

  /**
   * The label of the slide picker in the popover, shown below it and naming
   * it. It isn't shown on the button, whose tooltip shows the value, and it
   * isn't part of the button's name.
   */
  @property({attribute: 'slider-label'}) sliderLabel = '';
  /**
   * The name of the item the button sizes, such as a widget's title. Follows
   * the value in the button's name, so the buttons on several items are told
   * apart: "3 columns Recent Entries". Keep it in step with the item's name.
   */
  @property({attribute: 'item-label'}) itemLabel = '';
  /** The selected value. */
  @property({type: Number}) value = 0;
  /** Lowest selectable value. */
  @property({type: Number}) min = 0;
  /** Highest selectable value. */
  @property({type: Number}) max = 100;
  /** Spacing between selectable values. */
  @property({type: Number}) step = 10;
  /**
   * Unit shown and announced after the value, as `craft-slide-picker` takes
   * it: a word gets a space ("3 columns"), a symbol doesn't ("100%"). The
   * value starts the button's name, so set this or `valueLabel` whenever the
   * number alone doesn't say what it counts: "50% width", not "50".
   */
  @property({attribute: 'value-unit'}) valueUnit = '';
  /**
   * Formats the value, for when a number and a unit are not enough. A
   * property rather than an attribute, so bind it with `.valueLabel`.
   */
  @property({attribute: false}) valueLabel?: (value: number) => string;
  /** Opens the popover with a slide picker that can't be changed. */
  @property({type: Boolean, reflect: true}) readonly = false;
  /** Passed to the button; see `craft-button`'s `flush`. */
  @property() flush?: string;

  override _formValue(): FormValue {
    return String(this.value);
  }

  override _restoreFormValue(value: FormValue): void {
    this.value = Number(value ?? this.min);
  }

  #valueText(value: number): string {
    if (this.valueLabel) {
      return this.valueLabel(value);
    }
    const unit = this.valueUnit.trim();
    const separator = /^\p{L}/u.test(unit) ? ' ' : '';
    return unit ? `${value}${separator}${unit}` : `${value}`;
  }

  #onLabelClick = (): void => {
    this.renderRoot.querySelector('craft-slide-picker')?.focus();
  };

  #onPickerInput = (event: Event): void => {
    event.stopPropagation();
  };

  #onPickerChange = (event: Event): void => {
    event.stopPropagation();
    this.value = (event.target as CraftSlidePicker).value;
    emitInput(this);
    emitChange(this);
  };

  override updated(changed: PropertyValues<this>) {
    super.updated(changed);

    // `_formDisabled` changes while `disabled` is reflected, after `render()`.
    const invoker = this.renderRoot.querySelector<CraftButton>('craft-button');
    if (invoker) {
      invoker.disabled = this.disabled || this._formDisabled;
    }
  }

  override render() {
    const steps: number[] = [];
    for (let value = this.min; value <= this.max; value += this.step) {
      steps.push(value);
    }
    const valueText = this.#valueText(this.value);

    return html`
      <craft-popover placement="bottom">
        <craft-button
          id="invoker"
          slot="invoker"
          type="button"
          variant="plain"
          size="small"
          flush=${ifDefined(this.flush)}
          aria-labelledby="value item-label"
        >
          <span class="segments" aria-hidden="true">
            ${steps.map(
              (value) =>
                html`<span
                  class=${classMap({
                    segment: true,
                    'is-active': value <= this.value,
                    'is-last-active': value === this.value,
                  })}
                ></span>`
            )}
          </span>
        </craft-button>
        <div class="content" slot="content-body">
          <craft-slide-picker
            labelled-by="slider-label"
            value-unit=${this.valueUnit}
            .valueLabel=${this.valueLabel}
            .min=${this.min}
            .max=${this.max}
            .step=${this.step}
            .value=${this.value}
            ?readonly=${this.readonly}
            @input=${this.#onPickerInput}
            @change=${this.#onPickerChange}
          ></craft-slide-picker>
          <span id="slider-label" class="label" @click=${this.#onLabelClick}
            >${this.sliderLabel}</span
          >
        </div>
      </craft-popover>
      <span id="item-label" hidden>${this.itemLabel}</span>
      <craft-tooltip for="invoker" invoker-relation="label">
        <span slot="content" id="value">${valueText}</span>
      </craft-tooltip>
    `;
  }
}

if (!customElements.get('craft-slide-picker-button')) {
  customElements.define('craft-slide-picker-button', CraftSlidePickerButton);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-slide-picker-button': CraftSlidePickerButton;
  }
}
