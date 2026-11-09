import {html, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import CraftInput from '../input/input.js';
import {emitChange} from '@src/utilities/form-events';
import {t} from '@src/utilities/translate';
import styles from './input-color.styles.js';

const HEX_COLOR_PATTERN = /^[0-9a-f]{6}(?:[0-9a-f]{2})?$/i;
const SHORT_HEX_COLOR_PATTERN = /^[0-9a-f]{3,4}$/i;
const SRGB_COLOR_PATTERN =
  /^color\(srgb\s+([\d.e+-]+)\s+([\d.e+-]+)\s+([\d.e+-]+)(?:\s*\/\s*([\d.e+-]+))?\)$/;

const presetsConverter = {
  fromAttribute(value: string | null): string[] {
    if (!value) {
      return [];
    }

    try {
      const parsedValue = JSON.parse(value);

      if (!Array.isArray(parsedValue)) {
        return [];
      }

      return parsedValue.map((preset) => String(preset));
    } catch {
      return [];
    }
  },
};

function normalizeColorValue(value: unknown): string {
  return String(value ?? '')
    .trim()
    .replace(/^#/, '');
}

function expandedHexValue(value: unknown): string | null {
  const normalizedValue = normalizeColorValue(value);

  if (SHORT_HEX_COLOR_PATTERN.test(normalizedValue)) {
    return normalizedValue
      .split('')
      .map((character) => character + character)
      .join('');
  }

  if (HEX_COLOR_PATTERN.test(normalizedValue)) {
    return normalizedValue;
  }

  return null;
}

/**
 * Converts a native color input's value to a bare hex value. Color inputs with
 * the `alpha` attribute serialize their values as `color(srgb r g b / a)`; the
 * alpha channel is only included when the color isn't fully opaque.
 */
export function colorPickerValueToHex(value: string): string {
  if (value.startsWith('#')) {
    return value.substring(1);
  }

  const match = value.match(SRGB_COLOR_PATTERN);
  if (!match) {
    return value;
  }

  const hex = (n: string) =>
    Math.round(Math.min(Math.max(parseFloat(n), 0), 1) * 255)
      .toString(16)
      .padStart(2, '0');

  const [, red = '', green = '', blue = '', alpha] = match;
  let hexValue = hex(red) + hex(green) + hex(blue);
  if (alpha !== undefined && parseFloat(alpha) < 1) {
    hexValue += hex(alpha);
  }

  return hexValue;
}

/**
 * @summary A color input built on top of `craft-input`. Pairs a free
 * text field (prefixed with `#`) with a native `<input type="color">` swatch and
 * an optional datalist of preset colors.
 *
 * Values are normalized to bare lowercase-friendly hex throughout (the leading
 * `#` is stripped and 3- or 4-digit shorthand is expanded to 6 or 8 digits for
 * the picker), so the model value is always store-ready. The native picker is
 * only rendered when the browser supports `<input type="color">`.
 *
 * 8-digit hex values include an alpha channel. With `alpha` set, the picker
 * offers opacity too, in browsers that support its `alpha` attribute.
 *
 * @slot input - The text input element (provided by default; override to
 * customize).
 * @slot label - Field label.
 * @slot help-text - Supplementary help text.
 * @slot feedback - Validation feedback.
 *
 * @since 1.0
 */
export default class CraftInputColor extends CraftInput {
  static override get styles() {
    return [...super.styles, styles];
  }

  /**
   * Cached result of {@link doesBrowserSupportColorInputs} (`null` until first
   * probed).
   */
  static _browserSupportsColorInputs: boolean | null = null;

  /**
   * Cached result of {@link doesBrowserSupportAlphaColorInputs} (`null` until
   * first probed).
   */
  static _browserSupportsAlphaColorInputs: boolean | null = null;

  /**
   * Preset colors offered in the picker's datalist. Accepts an array, or a JSON
   * array string via the `presets` attribute. Each value may be `#`-prefixed or
   * bare, 3- or 6-digit hex; invalid entries are dropped.
   */
  @property({converter: presetsConverter})
  presets: string[] = [];

  /**
   * Whether the native picker should let authors set the color's opacity, in
   * browsers that support the color input's `alpha` attribute.
   */
  @property({type: Boolean, reflect: true})
  alpha = false;

  protected _pickerListId = `${this._inputId}-presets`;

  /**
   * Fixed to `text`. The swatch beside the field is a separate native color
   * input, so the text field keeps the hex value editable.
   */
  override type = 'text';

  constructor() {
    super();
  }

  /**
   * Whether the browser supports `<input type="color">`. Probed once by creating
   * a throwaway input and checking whether the `color` type stuck; the result is
   * cached on {@link _browserSupportsColorInputs}. When unsupported, the swatch
   * picker is omitted and only the text field is shown.
   */
  static doesBrowserSupportColorInputs(): boolean {
    if (CraftInputColor._browserSupportsColorInputs === null) {
      const input = document.createElement('input');
      input.setAttribute('type', 'color');
      CraftInputColor._browserSupportsColorInputs = input.type === 'color';
    }

    return CraftInputColor._browserSupportsColorInputs;
  }

  /**
   * Whether the browser supports the `alpha` attribute on
   * `<input type="color">`. Probed once and cached on
   * {@link _browserSupportsAlphaColorInputs}.
   */
  static doesBrowserSupportAlphaColorInputs(): boolean {
    if (CraftInputColor._browserSupportsAlphaColorInputs === null) {
      CraftInputColor._browserSupportsAlphaColorInputs =
        'alpha' in document.createElement('input');
    }

    return CraftInputColor._browserSupportsAlphaColorInputs;
  }

  /**
   * Adds the color swatch alongside the native input.
   */
  override get slots() {
    return {
      ...super.slots,
      input: () => {
        const input = document.createElement('input');
        const value = this.getAttribute('value');

        input.type = 'text';
        input.inputMode = 'text';
        input.spellcheck = false;
        input.setAttribute('autocorrect', 'off');
        input.setAttribute('autocapitalize', 'off');

        if (value) {
          input.setAttribute('value', normalizeColorValue(value));
        }

        return input;
      },
    };
  }

  /** The color as hex digits, without a leading `#`. */
  override get value(): string {
    return super.value;
  }

  override set value(value: string) {
    super.value = normalizeColorValue(value);
  }

  override connectedCallback() {
    super.connectedCallback();
    this.#normalizeInput();
    this._control?.addEventListener('input', this.#normalizeInput);
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this._control?.removeEventListener('input', this.#normalizeInput);
  }

  /**
   * Normalizes the value the input arrives with, such as server-rendered
   * markup's, and what is typed as it arrives, so a value with or without a
   * leading `#` lands the same way. Emits no event of its own.
   */
  #normalizeInput = (): void => {
    const input = this._control;

    if (input && normalizeColorValue(input.value) !== input.value) {
      this.value = input.value;
    }
  };

  protected get _expandedHexValue(): string | null {
    return expandedHexValue(this.value);
  }

  /**
   * Browsers that don't support the picker's `alpha` attribute only accept
   * 6-digit hex values.
   */
  protected _pickerHex(hex: string): string {
    return this.alpha && CraftInputColor.doesBrowserSupportAlphaColorInputs()
      ? hex
      : hex.substring(0, 6);
  }

  protected get _pickerValue(): string {
    return this._expandedHexValue
      ? `#${this._pickerHex(this._expandedHexValue)}`
      : '#ffffff';
  }

  protected get _validPresets(): string[] {
    return this.presets
      .map((preset) => expandedHexValue(preset))
      .filter((preset): preset is string => preset !== null)
      .map((preset) => `#${this._pickerHex(preset)}`);
  }

  protected _handlePickerInput(event: Event) {
    // The picker's own `input` event then carries on out of the shadow root,
    // with this component as its target.
    this.value = colorPickerValueToHex(
      (event.target as HTMLInputElement).value
    );
  }

  /**
   * The picker's `change` isn't composed, so it stays in the shadow root. The
   * commit is announced from the text input instead, as a typed one is.
   */
  protected _handlePickerChange(event: Event) {
    this._handlePickerInput(event);

    if (this._inputNode) {
      emitChange(this._inputNode);
    }
  }

  protected _pickerTemplate() {
    if (!CraftInputColor.doesBrowserSupportColorInputs()) {
      return nothing;
    }

    const presets = this._validPresets;

    return html`
      <input
        class="input-color__picker"
        type="color"
        aria-label="${t('Color picker')}"
        ?alpha="${this.alpha}"
        .value="${this._pickerValue}"
        ?disabled="${this.disabled}"
        list=${presets.length ? this._pickerListId : nothing}
        @input="${this._handlePickerInput}"
        @change="${this._handlePickerChange}"
      />
      ${presets.length
        ? html`
            <datalist id="${this._pickerListId}">
              ${presets.map(
                (preset) => html`<option value="${preset}"></option>`
              )}
            </datalist>
          `
        : nothing}
    `;
  }

  protected _swatchTemplate() {
    const previewStyle = this._expandedHexValue
      ? `background-color: #${this._expandedHexValue}`
      : '';

    return html`
      <label class="input-color__swatch">
        <span class="input-color__preview" style="${previewStyle}"></span>
        ${this._pickerTemplate()}
      </label>
    `;
  }

  override _inputGroupTemplate() {
    return html`
      <div class="input-color">
        ${this._inputGroupBeforeTemplate()}
        <div class="input-color__control">
          ${this._swatchTemplate()}
          <div class="input-group__container">
            <div class="input-group__prefix" aria-hidden="true">#</div>
            ${this._inputGroupInputTemplate()}
            ${this._inputGroupSuffixTemplate()}
          </div>
        </div>
        ${this._inputGroupAfterTemplate()}
      </div>
    `;
  }
}

if (!customElements.get('craft-input-color')) {
  customElements.define('craft-input-color', CraftInputColor);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-color': CraftInputColor;
  }
}
