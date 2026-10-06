import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftCombobox from './combobox.js';
import './combobox.js';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

/**
 * A single-choice combobox in a plain form — the shape a page that posts itself
 * uses, rather than going through an Inertia form.
 */
async function fixture(
  configure: (control: CraftCombobox) => void = () => {}
): Promise<{control: CraftCombobox; form: HTMLFormElement}> {
  const control = document.createElement('craft-combobox') as CraftCombobox;
  control.setAttribute('name', 'apiVersion');
  control.options = [
    {label: '2026-01', value: '2026-01'},
    {label: '2026-04', value: '2026-04'},
  ];
  configure(control);

  const form = document.createElement('form');
  form.append(control);
  document.body.append(form);
  await control.updateComplete;
  // Lion adopts a requested `model-value` only once the option naming it has
  // registered, so the value lands a tick after the first render.
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await control.updateComplete;

  return {control, form};
}

const entries = (form: HTMLFormElement) => [...new FormData(form).entries()];

it('posts its value under the host name', async () => {
  const {control, form} = await fixture((c) =>
    c.setAttribute('model-value', '2026-01')
  );

  expect(entries(form)).toEqual([['apiVersion', '2026-01']]);

  // Changing the selection changes what the form carries, so a save writes the
  // value the field is showing.
  control.modelValue = '2026-04';
  await control.updateComplete;

  expect(entries(form)).toEqual([['apiVersion', '2026-04']]);
});

it('posts a custom value when it does not match an option', async () => {
  const {control, form} = await fixture((c) => {
    c.requireOptionMatch = false;
    c.setAttribute('model-value', '$SHOPIFY_API_VERSION');
  });

  expect(entries(form)).toEqual([['apiVersion', '$SHOPIFY_API_VERSION']]);
  expect(control.modelValue).toBe('$SHOPIFY_API_VERSION');
});

it('posts nothing while disabled', async () => {
  const {control, form} = await fixture((c) => {
    c.setAttribute('model-value', '2026-01');
    c.disabled = true;
  });

  await control.updateComplete;

  expect(entries(form)).toEqual([]);
});
