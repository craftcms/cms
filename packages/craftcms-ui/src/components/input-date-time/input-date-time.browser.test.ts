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
  document.body.innerHTML = `
    <div style="width: ${width}px">
      <craft-input-date-time width="full">
        <craft-button icon="xmark-large" aria-label="Clear"></craft-button>
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

it('stacks the inputs, beside a full-height clear button, when they don’t fit', async () => {
  const {field, date, time, button} = await renderIn(200);
  const buttonBounds = button.getBoundingClientRect();

  expect(time.top).toBeGreaterThanOrEqual(date.bottom);
  expect(field.hasAttribute('stacked')).toBe(true);
  expect(buttonBounds.left).toBeGreaterThanOrEqual(date.right);
  expect(buttonBounds.top).toBeLessThanOrEqual(date.top);
  expect(buttonBounds.bottom).toBeGreaterThanOrEqual(time.bottom);
});
