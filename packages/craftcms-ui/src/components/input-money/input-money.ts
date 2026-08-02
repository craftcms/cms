import {html, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import CraftInput from '../input/input.js';
import styles from './input-money.styles.js';

/**
 * @summary A numeric money input with currency context and fraction-aware
 * stepping.
 *
 * @since 1.0
 */
export default class CraftInputMoney extends CraftInput {
  static override get styles() {
    return [...super.styles, styles];
  }

  /** ISO 4217 currency code. */
  @property({reflect: true}) currency: string | null = null;

  /** Display label shown before the amount. */
  @property({attribute: 'currency-label', reflect: true})
  currencyLabel: string | null = null;

  /** Number of fraction digits supported by the currency. */
  @property({attribute: 'fraction-digits', reflect: true, type: Number})
  fractionDigits = 2;

  /** Treats the model value as minor currency units. */
  @property({attribute: 'minor-units', reflect: true, type: Boolean})
  minorUnits = false;

  /** Decimal separator accepted by the formatting locale. */
  @property({attribute: 'decimal-separator', reflect: true})
  decimalSeparator = '.';

  /** Grouping separator accepted by the formatting locale. */
  @property({attribute: 'group-separator', reflect: true})
  groupSeparator = ',';

  constructor() {
    super();
    this.type = 'text';
  }

  override parser(value: string) {
    if (!this.minorUnits || value === '') {
      return value;
    }

    const normalized = value.split(this.groupSeparator).join('');
    const sign = normalized.startsWith('-') ? '-' : '';
    const [whole = '0', fraction = ''] = normalized
      .replace(/^-/, '')
      .split(this.decimalSeparator);
    const amount =
      `${whole || '0'}${fraction.padEnd(this.fractionDigits, '0').slice(0, this.fractionDigits)}`.replace(
        /^0+(?=\d)/,
        ''
      );
    const minorUnits = `${sign}${amount}`;
    const numericValue = Number(minorUnits);

    return Number.isSafeInteger(numericValue) ? numericValue : minorUnits;
  }

  override formatter(value: unknown) {
    if (!this.minorUnits) {
      return super.formatter(value);
    }

    if (value === null || value === undefined || value === '') {
      return '';
    }

    const raw = String(value);
    const sign = raw.startsWith('-') ? '-' : '';
    const amount = raw.replace(/^-/, '').padStart(this.fractionDigits + 1, '0');

    if (this.fractionDigits === 0) {
      return `${sign}${amount}`;
    }

    return `${sign}${amount.slice(0, -this.fractionDigits)}${this.decimalSeparator}${amount.slice(-this.fractionDigits)}`;
  }

  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    this._inputNode.type = 'text';
    this._inputNode.inputMode = 'decimal';
    this._inputNode.removeAttribute('step');
    this._inputNode.pattern = this._inputPattern();
  }

  protected override _inputGroupBeforeTemplate() {
    const label = this.currencyLabel || this.currency;

    return html`
      ${label ? html`<span data-money-currency>${label}</span>` : nothing}
      ${super._inputGroupBeforeTemplate()}
    `;
  }

  private _inputPattern(): string {
    const grouping = this.groupSeparator.replace(/[\\\]\-^]/g, '\\$&');
    const integer = `[0-9${grouping}]+`;

    if (this.fractionDigits === 0) {
      return `-?${integer}`;
    }

    const decimal = this.decimalSeparator.replace(
      /[.*+?^${}()|[\]\\]/g,
      '\\$&'
    );

    return `-?${integer}(?:${decimal}[0-9]{0,${this.fractionDigits}})?`;
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
