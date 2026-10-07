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
