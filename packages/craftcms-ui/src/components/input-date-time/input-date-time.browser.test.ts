import {beforeEach, expect, it} from 'vite-plus/test';
import '../button/button.js';
import './input-date-time.js';
import type CraftInputDateTime from './input-date-time.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(width: string | null): Promise<{
  field: CraftInputDateTime;
  inputs: HTMLElement[];
  button: HTMLElement;
}> {
  document.body.innerHTML = `
    <div style="width: 800px">
      <craft-input-date-time ${width ? `width="${width}"` : ''}>
        <craft-button icon="xmark-large" aria-label="Clear"></craft-button>
      </craft-input-date-time>
    </div>`;
  const field = document.querySelector('craft-input-date-time')!;
  await field.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));

  return {
    field,
    inputs: Array.from(
      field.querySelectorAll<HTMLElement>('[data-date-time-part]')
    ),
    button: field.querySelector('craft-button')!,
  };
}

function nativeWidth(input: HTMLElement): number {
  return input.querySelector('input')!.getBoundingClientRect().width;
}

it('stretches the inputs across the container with width="full"', async () => {
  const {field, inputs, button} = await render('full');
  const [date, time] = inputs;

  expect(button.getBoundingClientRect().right).toBeCloseTo(
    field.getBoundingClientRect().right,
    0
  );
  expect(nativeWidth(date!) + nativeWidth(time!)).toBeGreaterThan(600);
});

it('sizes the inputs to their content by default', async () => {
  const {inputs} = await render(null);
  const [date, time] = inputs;

  expect(nativeWidth(date!) + nativeWidth(time!)).toBeLessThan(600);
});

async function renderIn(width: number) {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <div style="width: ${width}px">
      <craft-input-date-time width="full">
        <craft-button size="small" variant="plain" icon="xmark-large" aria-label="Clear"></craft-button>
      </craft-input-date-time>
    </div>`;
  const field = document.querySelector('craft-input-date-time')!;
  await field.updateComplete;
  // The stacked state follows a frame after layout.
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await new Promise((resolve) => requestAnimationFrame(resolve));

  const [date, time] = Array.from(
    field.querySelectorAll<HTMLElement>('[data-date-time-part]')
  ).map((part) => part.getBoundingClientRect());

  return {
    field,
    date: date!,
    time: time!,
    button: field.querySelector('craft-button')!,
  };
}

it('keeps the inputs on one row when they fit', async () => {
  const {field, date, time} = await renderIn(800);

  // Same row: the time input starts before the date input ends.
  expect(time.top).toBeLessThan(date.bottom);
  expect(field.hasAttribute('stacked')).toBe(false);
});

it('stacks the inputs, with the clear button beside the last one, when they don’t fit', async () => {
  const {field, date, time, button} = await renderIn(200);
  const buttonBounds = button.getBoundingClientRect();

  expect(time.top).toBeGreaterThanOrEqual(date.bottom);
  expect(field.hasAttribute('stacked')).toBe(true);
  expect(buttonBounds.left).toBeGreaterThanOrEqual(
    field.shadowRoot!.querySelector('.inputs')!.getBoundingClientRect().right
  );
  expect(
    Math.abs(
      buttonBounds.top + buttonBounds.height / 2 - (time.top + time.bottom) / 2
    )
  ).toBeLessThanOrEqual(1);
});

it('runs the stacked divider and focus ring across the whole field, beside the clear button', async () => {
  const {field, button} = await renderIn(200);
  const parts = field.querySelectorAll<HTMLElement>('[data-date-time-part]');
  const last = parts[parts.length - 1]!;
  last.querySelector('input')!.focus();
  const fieldBounds = field.getBoundingClientRect();
  const fieldStyle = getComputedStyle(field);
  const lastBounds = last.getBoundingClientRect();

  expect(fieldStyle.backgroundImage).toContain('linear-gradient');
  expect(getComputedStyle(last).borderTopColor).toBe('rgba(0, 0, 0, 0)');
  expect(lastBounds.right).toBeCloseTo(
    fieldBounds.right - parseFloat(fieldStyle.borderRightWidth),
    0
  );
  expect(lastBounds.right).toBeGreaterThan(
    button.getBoundingClientRect().right
  );
  expect(getComputedStyle(last).outlineStyle).toBe('solid');
  expect(getComputedStyle(last.querySelector('input')!).outlineWidth).toBe(
    '0px'
  );
});

it('draws a softer divider between the inputs than the outer border', async () => {
  const {field} = await renderIn(800);
  const time = field.querySelectorAll<HTMLElement>('[data-date-time-part]')[1]!;

  expect(getComputedStyle(time).borderInlineStartColor).not.toBe(
    getComputedStyle(field).borderTopColor
  );
});

it('draws the clear button at its own size inside the border, without a divider', async () => {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <craft-input-date-time>
      <craft-button size="small" variant="plain" icon="xmark-large" aria-label="Clear"></craft-button>
    </craft-input-date-time>`;
  const field = document.querySelector<CraftInputDateTime>(
    'craft-input-date-time'
  )!;
  await field.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));
  const button = field.querySelector('craft-button')!;
  const buttonBounds = button.getBoundingClientRect();
  const fieldBounds = field.getBoundingClientRect();
  const buttonStyle = getComputedStyle(button);

  expect(buttonStyle.borderInlineStartColor).not.toBe(
    getComputedStyle(field).borderTopColor
  );
  expect(buttonStyle.borderTopLeftRadius).not.toBe('0px');
  expect(buttonBounds.top).toBeGreaterThan(fieldBounds.top + 1);
  expect(buttonBounds.bottom).toBeLessThan(fieldBounds.bottom - 1);
  expect(buttonBounds.right).toBeLessThan(fieldBounds.right - 1);
});
