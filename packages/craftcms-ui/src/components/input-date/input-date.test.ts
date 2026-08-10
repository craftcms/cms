import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInputDate from './input-date.js';
import './input-date.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-date', () => {
  it('uses the native date input', async () => {
    const element = document.createElement(
      'craft-input-date'
    ) as CraftInputDate;
    element.modelValue = '2026-08-05';
    document.body.append(element);
    await element.updateComplete;

    const input = element.querySelector<HTMLInputElement>(
      'input[slot="input"]'
    );

    expect(input?.type).toBe('date');
    expect(input?.value).toBe('2026-08-05');
  });
});
