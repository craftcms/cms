import {beforeEach, expect, it, vi} from 'vite-plus/test';
import type CraftCombobox from './combobox.js';
import './combobox.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function fixture(
  options: CraftCombobox['options']
): Promise<CraftCombobox> {
  const control = document.createElement('craft-combobox') as CraftCombobox;
  control.options = options;
  document.body.append(control);
  await control.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await control.updateComplete;

  return control;
}

it('colours an option’s indicator', async () => {
  const control = await fixture([
    {label: 'Online', value: 'true', data: {indicator: {fill: 'success'}}},
    {label: 'Offline', value: 'false', data: {indicator: {fill: 'danger'}}},
  ]);

  const fills = [...control.querySelectorAll('craft-indicator')].map((el) =>
    el.getAttribute('fill')
  );

  expect(fills).toEqual(['success', 'danger']);
});

it('leaves an indicator without a fill on the component’s default', async () => {
  const control = await fixture([
    {label: 'Plain', value: 'plain', data: {indicator: {}}},
  ]);

  const indicator = control.querySelector('craft-indicator')!;

  expect(indicator.getAttribute('fill')).toBe('var(--c-color-fill-loud)');
});

it('renders an indicator’s appearance', async () => {
  const control = await fixture([
    {label: 'Off', value: 'off', data: {indicator: {appearance: 'outline'}}},
  ]);

  expect(
    control.querySelector('craft-indicator')!.getAttribute('appearance')
  ).toBe('outline');
});

it('colours an option’s icon', async () => {
  const control = await fixture([
    {label: 'Red', value: 'red', data: {icon: 'circle', color: 'danger'}},
  ]);

  expect(control.querySelector('craft-icon')!.getAttribute('style')).toContain(
    '--icon-color: danger'
  );
});

it('leaves an icon without a colour unstyled', async () => {
  const control = await fixture([
    {label: 'Plain', value: 'plain', data: {icon: 'circle'}},
  ]);

  expect(control.querySelector('craft-icon')!.hasAttribute('style')).toBe(
    false
  );
});

it('shows the selected option’s indicator while closed', async () => {
  const control = await fixture([
    {label: 'Online', value: 'true', data: {indicator: {fill: 'success'}}},
    {label: 'Offline', value: 'false', data: {indicator: {fill: 'danger'}}},
  ]);
  control.modelValue = 'false';
  await control.updateComplete;

  const indicator = control.shadowRoot!.querySelector(
    '.prefix craft-indicator'
  )!;

  expect(indicator.getAttribute('fill')).toBe('danger');
  expect(control.hasAttribute('has-prefix-icon')).toBe(true);
});

it.each([
  {width: 200, expected: 'capped at 360px'},
  {width: 380, expected: 'as wide as a wider field'},
])(
  'keeps the list within the viewport when an option is long, $expected',
  async ({width}) => {
    await import('../../styles/cp.css');
    const {userEvent} = await import('vite-plus/test/browser');
    const container = document.createElement('div');
    container.style.inlineSize = `${width}px`;
    const control = document.createElement('craft-combobox') as CraftCombobox;
    control.showAllOnEmpty = true;
    control.options = [
      {label: 'short', value: 'short'},
      {label: 'long', value: 'long', data: {hint: 'x '.repeat(200)}},
    ];
    container.append(control);
    document.body.append(container);
    await control.updateComplete;

    await userEvent.click(control.querySelector('input')!);
    const listbox = control.querySelector<HTMLElement>('[slot="listbox"]')!;
    await vi.waitFor(() =>
      expect(listbox.querySelectorAll('craft-option')).toHaveLength(2)
    );
    await new Promise((resolve) => setTimeout(resolve, 300));

    const field = control.getBoundingClientRect();
    const list = listbox.getBoundingClientRect();

    expect(list.left).toBeGreaterThanOrEqual(0);
    expect(list.right).toBeLessThanOrEqual(window.innerWidth);
    expect(list.width).toBeLessThanOrEqual(Math.max(360, field.width) + 1);
    expect(list.width).toBeGreaterThanOrEqual(field.width - 1);
  }
);

it('opens and closes its options from the chevron, focusing the textbox', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const control = await fixture([
    {label: 'Anthropic', value: 'anthropic'},
    {label: 'Azure', value: 'azure'},
  ]);
  control.modelValue = 'anthropic';
  await control.updateComplete;
  const chevron = control.shadowRoot!.querySelector<HTMLElement>('.indicator')!;

  await userEvent.click(chevron);
  await new Promise((resolve) => setTimeout(resolve, 50));

  expect(document.activeElement).toBe(control.querySelector('input'));
  expect(control.opened).toBe(true);

  await userEvent.click(chevron);
  await new Promise((resolve) => setTimeout(resolve, 50));

  expect(control.opened).toBe(false);
});

it('gives the whole end of the field beside the clear button to the chevron, with a text cursor', async () => {
  const control = await fixture([{label: 'Anthropic', value: 'anthropic'}]);
  control.style.width = '300px';
  await control.updateComplete;
  const field = control
    .querySelector('input')!
    .closest('.form-control')!
    .getBoundingClientRect();
  const chevron = control.shadowRoot!.querySelector<HTMLElement>('.indicator')!;
  const box = chevron.getBoundingClientRect();

  expect(box.right).toBeCloseTo(field.right, 0);
  expect(box.top).toBeLessThanOrEqual(field.top + 1);
  expect(box.bottom).toBeGreaterThanOrEqual(field.bottom - 1);
  expect(getComputedStyle(chevron).cursor).toBe('text');
});
