import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInputMoney from './input-money.js';
import './input-money.js';

async function createInputMoney(): Promise<CraftInputMoney> {
  const element = document.createElement('craft-input-money');
  element.label = 'Price';
  element.locale = 'de_DE';
  element.currency = 'EUR';
  document.body.append(element);
  await element.updateComplete;

  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-money', () => {
  it('masks values using the currency locale', async () => {
    const element = await createInputMoney();
    const input = element.querySelector(
      'input[slot="input"]'
    ) as HTMLInputElement & {
      inputmask: {opts: Record<string, unknown>};
    };

    expect(input.inputmask.opts).toMatchObject({
      digits: 2,
      groupSeparator: '.',
      radixPoint: ',',
    });
    expect(element.shadowRoot?.querySelector('.currency')?.textContent).toBe(
      '(EUR) €'
    );
  });

  it('applies the mask without dispatching user input', async () => {
    const element = document.createElement('craft-input-money');
    element.label = 'Price';
    const inputEvents: Event[] = [];
    const userChanges: Event[] = [];
    element.addEventListener('input', (event) => inputEvents.push(event));
    element.addEventListener('model-value-changed', (event) => {
      if ((event as CustomEvent).detail?.isTriggeredByUser) {
        userChanges.push(event);
      }
    });

    document.body.append(element);
    element.modelValue = '1234.56';
    element.locale = 'de_DE';
    element.currency = 'EUR';
    await element.updateComplete;

    expect(inputEvents).toEqual([]);
    expect(userChanges).toEqual([]);
  });

  it('clears its value', async () => {
    const element = await createInputMoney();
    element.modelValue = '12,50';
    await element.updateComplete;

    const button = element.shadowRoot?.querySelector<HTMLElement>('.clear');
    button?.click();
    await element.updateComplete;

    expect(element.modelValue).toBe('');
    expect(button?.hidden).toBe(true);
  });

  it('keeps its mask after being moved', async () => {
    const element = await createInputMoney();
    const container = document.createElement('div');
    document.body.append(container);
    container.append(element);
    await element.updateComplete;

    const input = element.querySelector(
      'input[slot="input"]'
    ) as HTMLInputElement & {inputmask?: {opts: Record<string, unknown>}};

    expect(input.inputmask?.opts).toMatchObject({radixPoint: ','});
  });

  it('falls back to a two-decimal amount for a currency code Intl rejects', async () => {
    const element = await createInputMoney();
    element.currency = 'EURO';
    await element.updateComplete;

    const input = element.querySelector(
      'input[slot="input"]'
    ) as HTMLInputElement & {inputmask: {opts: Record<string, unknown>}};

    expect(element.shadowRoot?.querySelector('.currency')?.textContent).toBe(
      '(EURO) '
    );
    expect(input.inputmask.opts).toMatchObject({digits: 2, radixPoint: ','});
  });

  it('accepts false for default-true HTML attributes', async () => {
    const element = await createInputMoney();
    element.setAttribute('show-currency', 'false');
    element.setAttribute('clearable', 'false');
    element.modelValue = '12,50';
    await element.updateComplete;

    expect(element.showCurrency).toBe(false);
    expect(element.clearable).toBe(false);
    expect(element.shadowRoot?.querySelector('.currency')).toBeNull();
    expect(
      element.shadowRoot?.querySelector<HTMLButtonElement>('.clear')?.hidden
    ).toBe(true);
  });
});
