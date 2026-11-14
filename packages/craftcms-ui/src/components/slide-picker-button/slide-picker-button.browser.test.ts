import {beforeEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser';
import type CraftSlidePickerButton from './slide-picker-button.js';
import './slide-picker-button.js';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(markup: string): Promise<CraftSlidePickerButton> {
  document.body.innerHTML = markup;
  const button = document.querySelector<CraftSlidePickerButton>(
    'craft-slide-picker-button'
  )!;
  await button.updateComplete;
  return button;
}

function invoker(button: CraftSlidePickerButton): HTMLElement {
  return button.shadowRoot!.querySelector<HTMLElement>('craft-button')!;
}

function slider(button: CraftSlidePickerButton): HTMLElement {
  return button
    .shadowRoot!.querySelector('craft-slide-picker')!
    .querySelector<HTMLElement>('craft-slide-picker-slider')!;
}

async function open(button: CraftSlidePickerButton): Promise<void> {
  await userEvent.click(invoker(button));
  await expect.poll(() => slider(button).checkVisibility()).toBe(true);
}

it.each([
  {
    attributes: 'label="Width" value-unit="%" value="50"',
    name: 'Width 50%',
  },
  {
    attributes: 'label="Columns" button-label="Columns News" value="2"',
    name: 'Columns News 2',
  },
])('names the button $name', async ({attributes, name}) => {
  await render(
    `<craft-slide-picker-button min="25" max="100" step="25" ${attributes}></craft-slide-picker-button>`
  );

  await expect.element(page.getByRole('button')).toHaveAccessibleName(name);
});

it('names the button with valueLabel', async () => {
  const button = await render(
    `<craft-slide-picker-button label="Columns" min="1" max="3" step="1" value="1"></craft-slide-picker-button>`
  );
  button.valueLabel = (value) =>
    `${value} ${value === 1 ? 'column' : 'columns'}`;

  await expect
    .element(page.getByRole('button'))
    .toHaveAccessibleName('Columns 1 column');
});

it('names the popover’s slider by the label below it', async () => {
  const button = await render(
    `<craft-slide-picker-button label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button>`
  );
  await open(button);

  const label = button.shadowRoot!.querySelector<HTMLElement>('.label')!;
  await expect.element(page.getByRole('slider')).toHaveAccessibleName('Width');
  expect(label.getBoundingClientRect().top).toBeGreaterThanOrEqual(
    slider(button).getBoundingClientRect().bottom
  );
});

it('opens with Enter, sets the value from the slider and returns focus on Escape', async () => {
  const button = await render(
    `<craft-slide-picker-button label="Width" value-unit="%" min="25" max="100" step="25" value="50"></craft-slide-picker-button>`
  );
  const events: string[] = [];
  button.addEventListener('input', () => events.push(`input ${button.value}`));
  button.addEventListener('change', () =>
    events.push(`change ${button.value}`)
  );

  invoker(button).focus();
  await userEvent.keyboard('{Enter}');
  await expect.poll(() => slider(button).checkVisibility()).toBe(true);
  await userEvent.tab();
  expect(button.shadowRoot!.activeElement).toBe(slider(button));
  await userEvent.keyboard('{End}');

  expect(events).toEqual(['input 100', 'change 100']);
  await expect
    .element(page.getByRole('button'))
    .toHaveAccessibleName('Width 100%');

  await userEvent.keyboard('{Escape}');
  await expect.poll(() => slider(button).checkVisibility()).toBe(false);
  expect(button.shadowRoot!.activeElement).toBe(invoker(button));
});

it('keeps the popover’s slider from changing while read-only', async () => {
  const button = await render(
    `<craft-slide-picker-button label="Width" min="25" max="100" step="25" value="50" readonly></craft-slide-picker-button>`
  );
  await open(button);

  await expect
    .element(page.getByRole('slider'))
    .toHaveAttribute('aria-readonly', 'true');
});

it('disables the button when disabled, or inside a disabled fieldset', async () => {
  await render(
    `<fieldset><craft-slide-picker-button label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button></fieldset><craft-slide-picker-button label="Height" min="25" max="100" step="25" value="50" disabled></craft-slide-picker-button>`
  );
  const fieldset = document.querySelector('fieldset')!;

  await expect
    .element(page.getByRole('button', {name: 'Width 50'}))
    .toBeEnabled();
  await expect
    .element(page.getByRole('button', {name: 'Height 50'}))
    .toBeDisabled();

  fieldset.disabled = true;

  await expect
    .element(page.getByRole('button', {name: 'Width 50'}))
    .toBeDisabled();
});

it('is at least 24×24 CSS px, and so is each segment in the popover', async () => {
  const button = await render(
    `<craft-slide-picker-button label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button>`
  );
  const box = invoker(button).getBoundingClientRect();
  expect(box.width).toBeGreaterThanOrEqual(24);
  expect(box.height).toBeGreaterThanOrEqual(24);

  await open(button);
  const segments = [
    ...slider(button).shadowRoot!.querySelectorAll('.slide-picker__segment'),
  ];
  expect(segments).toHaveLength(4);
  for (const segment of segments) {
    const {width, height} = segment.getBoundingClientRect();
    expect(width).toBeGreaterThanOrEqual(24);
    expect(height).toBeGreaterThanOrEqual(24);
  }
});

it('wraps a long label to the slider’s width', async () => {
  const button = await render(
    `<craft-slide-picker-button label="Number of columns the widget spans on the dashboard" min="1" max="3" step="1" value="1"></craft-slide-picker-button>`
  );
  await open(button);

  const label = button.shadowRoot!.querySelector<HTMLElement>('.label')!;
  const sliderBox = slider(button).getBoundingClientRect();
  const labelBox = label.getBoundingClientRect();
  expect(labelBox.width).toBeLessThanOrEqual(sliderBox.width);
  expect(labelBox.height).toBeGreaterThan(
    2 * parseFloat(getComputedStyle(label).lineHeight)
  );
});

it('posts its value under its name, and puts it back on reset', async () => {
  const button = await render(
    `<form><craft-slide-picker-button name="width" label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button></form>`
  );
  const form = document.querySelector('form')!;
  expect(new FormData(form).get('width')).toBe('50');

  await open(button);
  slider(button).focus();
  await userEvent.keyboard('{End}');
  expect(new FormData(form).get('width')).toBe('100');

  form.reset();
  await button.updateComplete;
  expect(new FormData(form).get('width')).toBe('50');

  button.disabled = true;
  await button.updateComplete;
  expect(new FormData(form).has('width')).toBe(false);
});
