import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInputTime from './input-time.js';
import './input-time.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-time', () => {
  it('uses native constraints and rejects disabled ranges', async () => {
    const element = document.createElement(
      'craft-input-time'
    ) as CraftInputTime;
    element.minuteIncrement = 15;
    element.disabledTimeRanges = [['12:00', '13:00']];
    element.modelValue = '12:30';
    document.body.append(element);
    await element.updateComplete;

    const input = element.querySelector<HTMLInputElement>(
      'input[slot="input"]'
    )!;

    expect(input.type).toBe('time');
    expect(input.step).toBe('900');
    expect(input.validationMessage).toBe('This time is unavailable.');
  });

  it('rounds changed values when requested', async () => {
    const element = document.createElement(
      'craft-input-time'
    ) as CraftInputTime;
    element.minuteIncrement = 15;
    element.forceRoundTime = true;
    element.modelValue = '12:08';
    document.body.append(element);
    await element.updateComplete;

    const input = element.querySelector<HTMLInputElement>(
      'input[slot="input"]'
    )!;
    input.dispatchEvent(new Event('change', {bubbles: true}));

    expect(input.value).toBe('12:15');
    expect(element.modelValue).toBe('12:15');
  });
});
