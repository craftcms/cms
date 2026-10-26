import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {cdp, userEvent} from 'vite-plus/test/browser';
import type CraftRadio from '../radio/radio.js';
import type CraftRadioGroup from './radio-group.js';
import '../../styles/cp.css';
import '../icon/icon.js';
import '../radio/radio.js';
import './radio-group.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

afterEach(async () => {
  await cdp().send('Emulation.setEmulatedMedia', {features: []});
});

const settle = () => new Promise((resolve) => setTimeout(resolve));

/** Moves the pointer to an empty corner, so nothing it was left over stays hovered. */
async function parkPointer(): Promise<void> {
  const spot = document.createElement('div');
  spot.style.cssText =
    'position: fixed; inset-block-end: 0; inset-inline-end: 0; width: 10px; height: 10px;';
  document.body.append(spot);
  await userEvent.hover(spot);
  spot.remove();
}

async function render(): Promise<{
  group: CraftRadioGroup;
  radios: CraftRadio[];
  inputs: HTMLInputElement[];
}> {
  document.body.innerHTML = `
    <craft-radio-group buttons label="Position" style="--c-button-radius: 4px">
      ${['start', 'center', 'end']
        .map(
          (value) => `
        <div>
          <craft-radio>
            <input slot="input" type="radio" id="position-${value}" name="position" value="${value}"${value === 'center' ? ' checked' : ''}>
            <label slot="label" for="position-${value}">${value}</label>
          </craft-radio>
        </div>`
        )
        .join('')}
    </craft-radio-group>`;
  const group = document.querySelector('craft-radio-group')!;
  const radios = Array.from(document.querySelectorAll('craft-radio'));
  await Promise.all([
    group.updateComplete,
    ...radios.map((radio) => radio.updateComplete),
  ]);
  await settle();

  return {
    group,
    radios,
    inputs: radios.map((radio) => radio.querySelector('input')!),
  };
}

it('moves the selection with the arrow keys', async () => {
  const {group, inputs} = await render();

  await userEvent.tab();
  expect(document.activeElement).toBe(inputs[1]);

  await userEvent.keyboard('{ArrowRight}');
  await settle();
  expect(document.activeElement).toBe(inputs[2]);
  expect(group.modelValue).toBe('end');

  await userEvent.tab();
  expect(inputs).not.toContain(document.activeElement);
});

it('draws the focus ring around the focused button', async () => {
  const {radios, inputs} = await render();

  await userEvent.tab();
  await settle();

  const ring = inputs[1]!.getBoundingClientRect();
  const button = radios[1]!.getBoundingClientRect();
  const style = getComputedStyle(inputs[1]!);

  expect(style.outlineStyle).toBe('solid');
  expect(parseFloat(style.outlineWidth)).toBeGreaterThan(0);
  expect(ring.width).toBeCloseTo(button.width - 2);
  expect(ring.height).toBeCloseTo(button.height - 2);
  expect(Number(getComputedStyle(radios[1]!).zIndex)).toBeGreaterThan(
    Number(getComputedStyle(radios[0]!).zIndex) || 0
  );
});

it('selects an option when its button is clicked', async () => {
  const {group, radios} = await render();

  await userEvent.click(radios[0]!, {position: {x: 2, y: 2}});
  await parkPointer();
  await settle();

  expect(group.modelValue).toBe('start');
});

it('joins the buttons, rounding only the outer corners', async () => {
  const {radios} = await render();
  const [first, middle, last] = radios.map((radio) =>
    radio.getBoundingClientRect()
  );
  const radius = (radio: CraftRadio, corner: string) =>
    parseFloat(getComputedStyle(radio).getPropertyValue(corner));

  expect(middle!.left).toBeCloseTo(first!.right - 1);
  expect(last!.left).toBeCloseTo(middle!.right - 1);
  expect(middle!.top).toBeCloseTo(first!.top);
  expect(radius(radios[0]!, 'border-start-start-radius')).toBe(4);
  expect(radius(radios[0]!, 'border-start-end-radius')).toBe(0);
  expect(radius(radios[1]!, 'border-start-start-radius')).toBe(0);
  expect(radius(radios[1]!, 'border-start-end-radius')).toBe(0);
  expect(radius(radios[2]!, 'border-start-end-radius')).toBe(4);
});

it('fills the selected button and raises it', async () => {
  const {radios} = await render();
  const [, selected, unselected] = radios.map((radio) =>
    getComputedStyle(radio)
  );

  expect(selected!.backgroundColor).not.toBe(unselected!.backgroundColor);
  expect(selected!.color).not.toBe(unselected!.color);
  expect(Number(selected!.zIndex)).toBeGreaterThan(
    Number(unselected!.zIndex) || 0
  );
});

it('makes an icon-only option a square button', async () => {
  document.body.innerHTML = `
    <craft-radio-group buttons label="Position">
      <craft-radio>
        <input slot="input" type="radio" id="corner" name="corner" value="top">
        <label slot="label" for="corner"><craft-icon name="arrow-up" label="Top"></craft-icon></label>
      </craft-radio>
    </craft-radio-group>`;
  const group = document.querySelector('craft-radio-group')!;
  const radio = document.querySelector('craft-radio')!;
  await group.updateComplete;
  await radio.updateComplete;
  await settle();

  const {width, height} = radio.getBoundingClientRect();

  expect(radio.hasAttribute('data-icon-only')).toBe(true);
  expect(width).toBeCloseTo(height);
  expect(height).toBeGreaterThanOrEqual(24);
});

it('distinguishes the selected button in forced colors mode', async () => {
  await cdp().send('Emulation.setEmulatedMedia', {
    features: [{name: 'forced-colors', value: 'active'}],
  });
  const {radios} = await render();

  expect(matchMedia('(forced-colors: active)').matches).toBe(true);

  const [, selected, unselected] = radios.map((radio) =>
    getComputedStyle(radio)
  );

  expect(selected!.backgroundColor).not.toBe(unselected!.backgroundColor);
  expect(selected!.color).not.toBe(unselected!.color);
});

it('draws lighter borders between buttons than around the group', async () => {
  const {radios} = await render();
  const [first, , last] = radios.map((radio) => getComputedStyle(radio));

  expect(first!.borderInlineEndColor).not.toBe(first!.borderInlineStartColor);
  expect(last!.borderInlineStartColor).toBe(first!.borderInlineEndColor);
  expect(last!.borderInlineEndColor).toBe(first!.borderInlineStartColor);
});

it('gives only the unchecked buttons a pointer cursor', async () => {
  const {inputs} = await render();

  expect(inputs.map((input) => getComputedStyle(input).cursor)).toEqual([
    'pointer',
    'default',
    'pointer',
  ]);
});
