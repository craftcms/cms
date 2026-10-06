import {beforeEach, expect, it} from 'vite-plus/test';
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
