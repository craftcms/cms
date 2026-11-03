import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftCombobox from './combobox.js';
import './combobox.js';
import '../../styles/cp.css';
import {serializeFormInputsAsObject} from '../../utilities/dom.js';

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
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await control.updateComplete;

  return {control, form};
}

const entries = (form: HTMLFormElement) => [...new FormData(form).entries()];

it('posts its value under the host name', async () => {
  const {control, form} = await fixture((c) =>
    c.setAttribute('value', '2026-01')
  );

  expect(entries(form)).toEqual([['apiVersion', '2026-01']]);

  // Changing the selection changes what the form carries, so a save writes the
  // value the field is showing.
  control.value = '2026-04';
  await control.updateComplete;

  expect(entries(form)).toEqual([['apiVersion', '2026-04']]);
});

it('posts a custom value when it does not match an option', async () => {
  const {control, form} = await fixture((c) => {
    c.requireOptionMatch = false;
    c.setAttribute('value', '$SHOPIFY_API_VERSION');
  });

  expect(entries(form)).toEqual([['apiVersion', '$SHOPIFY_API_VERSION']]);
  expect(control.value).toBe('$SHOPIFY_API_VERSION');
});

it('posts a value its markup carried, with the options as markup too', async () => {
  // How a server-rendered page arrives: both `value` and `options` are
  // attributes on the element, and it upgrades with them already set.
  const options = JSON.stringify([
    {label: '$SHOPIFY_API_VERSION', value: '$SHOPIFY_API_VERSION'},
  ]);
  document.body.innerHTML = `<form><craft-combobox name="folderPath" value="@webroot/cache/blitz" options='${options}'></craft-combobox></form>`;

  const form = document.querySelector('form')!;
  const control = document.querySelector('craft-combobox') as CraftCombobox;
  await control.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await control.updateComplete;

  expect(control.value).toBe('@webroot/cache/blitz');
  expect(entries(form)).toEqual([['folderPath', '@webroot/cache/blitz']]);
});

it('serializes typed text that matches an option without choosing it', async () => {
  // The legacy HTML bridge reads values with `serializeFormInputsAsObject`,
  // not `FormData`. Typed text that only matched an env var's keywords used to
  // serialize as empty, so the field saved blank.
  const {userEvent} = await import('vite-plus/test/browser');
  const {control, form} = await fixture((c) => {
    c.requireOptionMatch = false;
    c.options = [
      {
        label: '$TO_EMAIL',
        value: '$TO_EMAIL',
        data: {hint: 'sales@example.com', keywords: 'sales@example.com'},
      },
    ];
  });
  const input = control.querySelector('input')!;

  await userEvent.click(input);
  await userEvent.type(input, 'sales@example');

  expect(control.querySelector('craft-option')).not.toBeNull();
  expect(serializeFormInputsAsObject(form)).toEqual({
    apiVersion: 'sales@example',
  });
});

it('posts nothing while disabled', async () => {
  const {control, form} = await fixture((c) => {
    c.setAttribute('value', '2026-01');
    c.disabled = true;
  });

  await control.updateComplete;

  expect(entries(form)).toEqual([]);
});
