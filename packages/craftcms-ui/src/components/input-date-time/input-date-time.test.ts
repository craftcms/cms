import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInput from '../input/input.js';
import type CraftInputDateTime from './input-date-time.js';
import './input-date-time.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-date-time', () => {
  it('owns its locale and timezone form metadata', async () => {
    const element = document.createElement(
      'craft-input-date-time'
    ) as CraftInputDateTime;
    element.name = 'startsAt';
    element.dateValue = '2026-08-05';
    element.timeValue = '12:30';
    element.locale = 'en-US';
    element.timezone = 'Europe/Brussels';
    document.body.append(element);
    await element.updateComplete;

    const date = element.querySelector<CraftInput>('craft-input-date')!;
    const time = element.querySelector<CraftInput>('craft-input-time')!;

    expect([date.name, date.modelValue]).toEqual([
      'startsAt[date]',
      '2026-08-05',
    ]);
    expect([time.name, time.modelValue]).toEqual(['startsAt[time]', '12:30']);

    date.modelValue = '2026-08-06';
    await date.updateComplete;
    expect(element.dateValue).toBe('2026-08-06');

    expect(
      [
        ...element.querySelectorAll<HTMLInputElement>('input[type="hidden"]'),
      ].map((input) => [input.name, input.value])
    ).toEqual([
      ['startsAt[locale]', 'en-US'],
      ['startsAt[timezone]', 'Europe/Brussels'],
    ]);

    element.showTimezone = true;
    await element.updateComplete;

    expect(element.querySelector('craft-input[name="startsAt[timezone]"]')).not
      .toBeNull;
    expect(
      element.querySelector('input[type="hidden"][name="startsAt[timezone]"]')
    ).toBeNull();
  });
});
