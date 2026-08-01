import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInputMoney from './input-money.js';
import './input-money.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-money', () => {
  it('accepts locale-aware values and exposes its currency context', async () => {
    const element = document.createElement('craft-input-money');
    const input = document.createElement('input');

    input.slot = 'input';
    input.name = 'amount[value]';
    input.value = '12,50';
    element.name = 'amount[value]';
    element.modelValue = '12,50';
    element.currency = 'USD';
    element.currencyLabel = '(USD) $';
    element.fractionDigits = 2;
    element.decimalSeparator = ',';
    element.groupSeparator = '.';
    element.append(input);
    document.body.append(element);
    await element.updateComplete;

    expect(
      element.shadowRoot?.querySelector('[data-money-currency]')?.textContent
    ).toBe('(USD) $');
    expect(
      element.shadowRoot
        ?.querySelector('[data-money-currency]')
        ?.hasAttribute('aria-hidden')
    ).toBe(false);
    expect(input.type).toBe('text');
    expect(input.inputMode).toBe('decimal');
    expect(input.pattern).toBe('-?[0-9.]+(?:,[0-9]{0,2})?');
    expect(input.name).toBe('amount[value]');
    expect(input.value).toBe('12,50');
  });

  it('falls back to the currency code and preserves an empty value', async () => {
    const element = document.createElement('craft-input-money');
    const input = document.createElement('input');

    input.slot = 'input';
    element.currency = 'JPY';
    element.fractionDigits = 0;
    element.append(input);
    document.body.append(element);
    await element.updateComplete;

    expect(
      element.shadowRoot?.querySelector('[data-money-currency]')?.textContent
    ).toBe('JPY');
    expect(input.type).toBe('text');
    expect(input.pattern).toBe('-?[0-9,]+');
    expect(input.value).toBe('');
  });
});
