import {expect, test} from 'vitest';
import {html, render} from 'lit';

import './input-date-time.js';

async function mount(template: unknown) {
  const host = document.createElement('div');
  document.body.append(host);
  render(template as never, host);
  const element = host.firstElementChild as HTMLElement & {
    updateComplete: Promise<unknown>;
  };
  await element.updateComplete;
  await new Promise((resolve) => setTimeout(resolve, 0));
  await element.updateComplete;
  return element;
}

function labels(element: HTMLElement): Record<string, string | null> {
  return Object.fromEntries(
    [...element.querySelectorAll('[data-date-time-part]')].map((part) => [
      (part as HTMLElement).dataset.dateTimePart!,
      part.getAttribute('aria-label'),
    ])
  );
}

test('names the date and time inputs separately', async () => {
  const element = await mount(
    html`<craft-input-date-time name="postDate"></craft-input-date-time>`
  );

  expect(labels(element)).toEqual({date: 'Date', time: 'Time'});
});

test('names the timezone input when it is shown', async () => {
  const element = await mount(
    html`<craft-input-date-time
      name="postDate"
      show-timezone
    ></craft-input-date-time>`
  );

  expect(labels(element).timezone).toBe('Time zone');
});

test('a date-only field still names its one input', async () => {
  const element = await mount(
    html`<craft-input-date-time
      name="postDate"
      show-time="false"
    ></craft-input-date-time>`
  );

  expect(labels(element)).toEqual({date: 'Date'});
});
