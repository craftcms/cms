import Inputmask from 'inputmask';
import {css, html, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {t} from '@src/utilities/translate';
import CraftInput from '../input/input.js';
import {defaultTrueBoolean} from '@src/utilities/converters';
import '../button/button.js';
import '../icon/icon.js';

type MaskedInput = HTMLInputElement & {
  inputmask?: {
    opts: Record<string, unknown>;
    remove(): void;
    setValue(value: string): void;
  };
};

type CurrencyFormat = {
  currency: string;
  decimal: string;
  group: string;
  decimals: number;
};

/**
 * Reads the symbol, separators and decimal places for a currency in a locale.
 * A currency code or locale `Intl` rejects falls back to a plain two-decimal
 * number, in the locale when it is valid, so the field still renders.
 */
function currencyFormat(locale: string, currency: string): CurrencyFormat {
  const plain = {minimumFractionDigits: 2, maximumFractionDigits: 2};
  const attempts: [string | undefined, Intl.NumberFormatOptions][] = [
    [locale, {style: 'currency', currency}],
    [locale, plain],
    [undefined, plain],
  ];

  for (const [attemptLocale, options] of attempts) {
    let formatter: Intl.NumberFormat;

    try {
      formatter = new Intl.NumberFormat(attemptLocale, options);
    } catch {
      continue;
    }

    const parts = formatter.formatToParts(12345.6);

    return {
      currency: parts.find(({type}) => type === 'currency')?.value ?? '',
      decimal: parts.find(({type}) => type === 'decimal')?.value ?? '.',
      group: parts.find(({type}) => type === 'group')?.value ?? ',',
      decimals: formatter.resolvedOptions().maximumFractionDigits ?? 2,
    };
  }

  return {currency: '', decimal: '.', group: ',', decimals: 2};
}

/**
 * @summary A locale-aware money input with a currency label and clear button.
 * Extends `craft-input`, so it carries the same label, help text, validation,
 * and slots.
 *
 * The field is a masked text input rather than `type="number"`: the mask is
 * built from `locale` and `currency` via `Intl.NumberFormat`, so the grouping
 * and decimal characters are the ones that locale actually uses. Override
 * either separator to depart from that.
 *
 * @dependency craft-button
 * @dependency craft-icon
 */
export default class CraftInputMoney extends CraftInput {
  static override get styles() {
    return [
      ...super.styles,
      css`
        .currency {
          white-space: nowrap;
        }

        .input-group__input {
          display: flex;
          align-items: center;
        }

        .clear {
          flex: none;
          margin-inline-end: var(--c-spacing-xs);
        }

        ::slotted(.money-placeholder) {
          color: var(--c-text-quiet);
        }
      `,
    ];
  }

  /** ISO 4217 currency code, which sets the symbol and the decimal places. */
  @property() currency = 'USD';

  /** Locale the amount is formatted for. Accepts `en-US` or `en_US`. */
  @property() locale = 'en-US';

  /**
   * Number of decimal places. Defaults to whatever the currency uses in this
   * locale — two for most, none for yen.
   */
  @property({type: Number}) decimals?: number;

  /** Character separating the decimals. Defaults to the locale's own. */
  @property({attribute: 'decimal-separator'}) decimalSeparator?: string;

  /** Character grouping the thousands. Defaults to the locale's own. */
  @property({attribute: 'group-separator'}) groupSeparator?: string;

  /**
   * Whether the currency symbol is shown beside the field. On by default —
   * set `show-currency="false"` to hide it.
   */
  @property({
    converter: defaultTrueBoolean,
    attribute: 'show-currency',
  })
  showCurrency = true;

  /**
   * Whether a button to clear the amount is shown once there is one. On by
   * default — set `clearable="false"` to remove it.
   */
  @property({converter: defaultTrueBoolean}) clearable = true;

  /**
   * Fixed to `text` rather than `number`: the value is a formatted, masked
   * amount, and a number input would fight the grouping and currency symbols.
   */
  override type = 'text';

  #format!: CurrencyFormat;

  constructor() {
    super();

    this.inputMode = 'decimal';
  }

  override connectedCallback() {
    super.connectedCallback();

    if (this.hasUpdated) {
      this.#applyMask();
    }
  }

  override willUpdate(changedProperties: PropertyValues) {
    super.willUpdate(changedProperties);

    if (
      !this.hasUpdated ||
      changedProperties.has('locale') ||
      changedProperties.has('currency')
    ) {
      this.#format = currencyFormat(
        this.locale.replaceAll('_', '-'),
        this.currency
      );
    }
  }

  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (
      changedProperties.has('currency') ||
      changedProperties.has('locale') ||
      changedProperties.has('decimals') ||
      changedProperties.has('decimalSeparator') ||
      changedProperties.has('groupSeparator') ||
      changedProperties.has('placeholder')
    ) {
      this.#applyMask();
    }

    this.#input()?.classList.toggle('money-placeholder', !this.#hasValue());
  }

  override disconnectedCallback() {
    this.#input()?.inputmask?.remove();
    super.disconnectedCallback();
  }

  #input(): MaskedInput | undefined {
    return this._inputNode as MaskedInput | undefined;
  }

  #applyMask() {
    const input = this.#input();

    if (!input) {
      return;
    }

    const value = String(this.modelValue ?? '');
    const format = this.#format;
    input.inputmask?.remove();
    // Seed the value before masking. `setValue()` afterwards would dispatch a
    // real `input` event, which the form layer reads as typing.
    input.value = value;
    new Inputmask({
      alias: 'currency',
      autoGroup: false,
      clearMaskOnLostFocus: true,
      digits: this.decimals ?? format.decimals,
      digitsOptional: false,
      groupSeparator: this.groupSeparator ?? format.group,
      placeholder: this.placeholder || '0',
      prefix: '',
      radixPoint: this.decimalSeparator ?? format.decimal,
    }).mask(input);
  }

  #hasValue(): boolean {
    return this.modelValue !== '' && this.modelValue != null;
  }

  #clear = () => {
    this.modelValue = '';
    this.value = '';
    this.#input()?.inputmask?.setValue('');
    this.#input()?.focus();
  };

  override _inputGroupPrefixTemplate() {
    if (!this.showCurrency) {
      return nothing;
    }

    return html`<div class="input-group__prefix">
      <span class="currency"
        >${`(${this.currency}) ${this.#format.currency}`}</span
      >
    </div>`;
  }

  override _inputGroupInputTemplate() {
    return html`<div class="input-group__input">
      <slot name="input"></slot>
      <craft-button
        class="clear"
        type="button"
        variant="plain"
        size="small"
        icon
        aria-label=${t('Clear')}
        ?hidden=${!this.clearable || this.disabled || !this.#hasValue()}
        @mousedown=${(event: Event) => event.preventDefault()}
        @click=${this.#clear}
      >
        <craft-icon name="xmark-large" style="font-size: 0.8em"></craft-icon>
      </craft-button>
    </div>`;
  }
}

if (!customElements.get('craft-input-money')) {
  customElements.define('craft-input-money', CraftInputMoney);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-money': CraftInputMoney;
  }
}
