import {LionInput} from '@lion/ui/input.js';
import {html, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import {inputStyles} from '@src/styles/form.styles';
import {t} from '@src/utilities/translate';
import styles from './input-color.styles.js';

const HEX_COLOR_PATTERN = /^[0-9a-f]{6}$/i;
const SHORT_HEX_COLOR_PATTERN = /^[0-9a-f]{3}$/i;

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

export default class CraftInputColor extends LionInput {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }

  static _browserSupportsColorInputs: boolean | null = null;

  @property({converter: presetsConverter})
  presets: string[] = [];

  protected _pickerListId = `${this._inputId}-presets`;

  constructor() {
    super();
    this.type = 'text';
  }

  static doesBrowserSupportColorInputs(): boolean {
    if (CraftInputColor._browserSupportsColorInputs === null) {
      const input = document.createElement('input');
      input.setAttribute('type', 'color');
      CraftInputColor._browserSupportsColorInputs = input.type === 'color';
    }

    return CraftInputColor._browserSupportsColorInputs;
  }

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

  override parser(value: string) {
    return normalizeColorValue(value);
  }

  override formatter(value: unknown) {
    return normalizeColorValue(value);
  }

  override serializer(value: unknown) {
    return normalizeColorValue(value);
  }

  override deserializer(value: string) {
    return normalizeColorValue(value);
  }

  override preprocessor(value: string) {
    const normalizedValue = normalizeColorValue(value);

    if (normalizedValue === value) {
      return undefined;
    }

    return normalizedValue;
  }

  protected get _expandedHexValue(): string | null {
    return expandedHexValue(this.modelValue);
  }

  protected get _pickerValue(): string {
    return this._expandedHexValue ? `#${this._expandedHexValue}` : '#ffffff';
  }

  protected get _validPresets(): string[] {
    return this.presets
      .map((preset) => expandedHexValue(preset))
      .filter((preset): preset is string => preset !== null)
      .map((preset) => `#${preset}`);
  }

  protected _handlePickerInput(event: Event) {
    const input = event.target as HTMLInputElement;
    const normalizedValue = normalizeColorValue(input.value);
    const wasHandlingUserInput = this._isHandlingUserInput;

    this._isHandlingUserInput = true;
    this.modelValue = normalizedValue;
    this.value = normalizedValue;
    this._isHandlingUserInput = wasHandlingUserInput;
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
        aria-controls="${this._inputId}"
        aria-label="${t('Color picker')}"
        .value="${this._pickerValue}"
        ?disabled="${this.disabled}"
        list=${presets.length ? this._pickerListId : nothing}
        @input="${this._handlePickerInput}"
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
