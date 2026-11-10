import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftInput from '../input/input.js';
import type CraftInputDateTime from './input-date-time.js';
import './input-date-time.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-date-time', () => {
  it('keeps its own inputs ahead of slotted content', async () => {
    const element = document.createElement(
      'craft-input-date-time'
    ) as CraftInputDateTime;
    element.name = 'startsAt';

    // A clear button, as `DateTimeControl` slots in: it acts on the inputs, so
    // it has to follow them in reading and tab order — but it's in the DOM
    // before they're created.
    const clear = document.createElement('button');
    clear.className = 'clear-btn';
    element.append(clear);

    document.body.append(element);
    await element.updateComplete;

    expect(
      [...element.children]
        .slice(0, 3)
        .map((child) => child.tagName.toLowerCase())
    ).toEqual(['craft-input-date', 'craft-input-time', 'button']);

    // Turning a part on later still lands it with the other inputs.
    element.showTimezone = true;
    await element.updateComplete;

    expect(
      [...element.children]
        .slice(0, 4)
        .map((child) => child.tagName.toLowerCase())
    ).toEqual([
      'craft-input-date',
      'craft-input-time',
      'craft-input',
      'button',
    ]);
  });

  it('marks both native inputs as required', async () => {
    const element = document.createElement(
      'craft-input-date-time'
    ) as CraftInputDateTime;
    element.required = true;
    document.body.append(element);
    await element.updateComplete;

    const natives = [
      ...element.querySelectorAll<HTMLInputElement>(
        '[data-date-time-part] input'
      ),
    ];
    await Promise.all(
      [...element.querySelectorAll<CraftInput>('[data-date-time-part]')].map(
        (part) => part.updateComplete
      )
    );

    expect(natives.map((input) => input.getAttribute('aria-required'))).toEqual(
      ['true', 'true']
    );
  });

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

    expect([date.name, date.value]).toEqual(['startsAt[date]', '2026-08-05']);
    expect([time.name, time.value]).toEqual(['startsAt[time]', '12:30']);

    // As typing does: the native input changes, then announces it.
    const dateInput = date.querySelector('input')!;
    dateInput.value = '2026-08-06';
    dateInput.dispatchEvent(
      new Event('input', {bubbles: true, composed: true})
    );
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

  it('keeps a time that force-round-time rounded', async () => {
    const element = document.createElement(
      'craft-input-date-time'
    ) as CraftInputDateTime;
    element.minuteIncrement = 15;
    element.forceRoundTime = true;
    document.body.append(element);
    await element.updateComplete;

    const time = element.querySelector<CraftInput>('craft-input-time')!;
    await time.updateComplete;
    const timeInput = time.querySelector('input')!;
    timeInput.value = '12:08';
    timeInput.dispatchEvent(
      new Event('input', {bubbles: true, composed: true})
    );
    timeInput.dispatchEvent(new Event('change', {bubbles: true}));
    await element.updateComplete;

    expect(element.timeValue).toBe('12:15');

    element.disabled = true;
    await element.updateComplete;

    expect(timeInput.value).toBe('12:15');
  });
});

describe('a clearable craft-input-date-time', () => {
  async function clearable(attrs = '') {
    document.body.innerHTML = `<craft-input-date-time name="d" date-value="2026-08-05" time-value="12:30" clearable ${attrs}></craft-input-date-time>`;
    const element = document.querySelector('craft-input-date-time')!;
    await element.updateComplete;

    return element;
  }

  const clearButton = (element: CraftInputDateTime) =>
    element.shadowRoot!.querySelector<HTMLElement>('[part="clear"]');

  it('clears its inputs and reports the change', async () => {
    const element = await clearable();
    const changed = vi.fn();
    element.addEventListener('change', changed);

    clearButton(element)!.click();
    await element.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    expect([element.dateValue, element.timeValue]).toEqual(['', '']);
    expect(changed).toHaveBeenCalledOnce();
    expect(clearButton(element)!.classList).toContain('invisible');
  });

  it('has no clear button when it is disabled', async () => {
    expect(clearButton(await clearable('disabled'))).toBeNull();
  });
});
