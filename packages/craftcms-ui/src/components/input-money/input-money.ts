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
