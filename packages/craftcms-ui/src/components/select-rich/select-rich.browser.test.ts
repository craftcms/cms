import {Required} from '@lion/ui/form-core.js';
import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import '../../styles/cp.css';
import './select-rich.js';
import type CraftSelectRich from './select-rich.js';

beforeEach(() => {
  document.body.innerHTML = '';
  document.documentElement.dataset.theme = 'dark';
});

afterEach(() => {
  delete document.documentElement.dataset.theme;
});

async function fixture(): Promise<CraftSelectRich> {
  document.body.innerHTML = `
    <craft-select-rich label="Status">
      <craft-option .choiceValue="all" checked>All</craft-option>
      <craft-option .choiceValue="live">Live</craft-option>
    </craft-select-rich>`;
  const select = document.querySelector<CraftSelectRich>('craft-select-rich')!;
  await select.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));

  return select;
}

// Lion's base styles hard-code light fills, which read as white-on-white
// in the dark theme.
it('leaves unselected options unfilled', async () => {
  const select = await fixture();
  const option = select.querySelectorAll('craft-option')[1]!;

  expect(getComputedStyle(option).backgroundColor).toBe('rgba(0, 0, 0, 0)');
});

it('doesn’t light up the hovered invoker', async () => {
  const select = await fixture();
  const invoker = select.querySelector<HTMLElement>('lion-select-invoker')!;

  await userEvent.hover(invoker);

  expect(getComputedStyle(invoker).backgroundColor).not.toBe(
    'rgb(244, 246, 247)'
  );
});

it.each([
  {width: '100%', expected: 'in a full-width container'},
  {width: '200px', expected: 'in a narrow container'},
])(
  'stays within its container and the viewport when an option is long, $expected',
  async ({width}) => {
    const {page} = await import('vite-plus/test/browser');
    await page.viewport(320, 640);
    document.body.innerHTML = `
      <div style="padding-inline: 16px">
        <div class="container" style="inline-size: ${width}">
          <craft-select-rich label="Status">
            <craft-option .choiceValue="short">Short</craft-option>
            <craft-option .choiceValue="long" hint="${'x '.repeat(200)}">${'y'.repeat(300)}</craft-option>
          </craft-select-rich>
        </div>
      </div>`;
    const container = document.querySelector<HTMLElement>('.container')!;
    const select =
      document.querySelector<CraftSelectRich>('craft-select-rich')!;
    await select.updateComplete;
    await new Promise((resolve) => setTimeout(resolve, 100));

    select.opened = true;
    const listbox = select.querySelector<HTMLElement>('[slot="input"]')!;
    await new Promise((resolve) => setTimeout(resolve, 300));

    const invoker = select
      .querySelector<HTMLElement>('lion-select-invoker')!
      .getBoundingClientRect();
    const list = listbox.getBoundingClientRect();

    expect(invoker.right).toBeLessThanOrEqual(
      container.getBoundingClientRect().right + 1
    );
    expect(list.left).toBeGreaterThanOrEqual(0);
    expect(list.right).toBeLessThanOrEqual(window.innerWidth);
  }
);

async function formFixture(): Promise<{
  form: HTMLFormElement;
  select: CraftSelectRich;
}> {
  document.body.innerHTML = `
    <form>
      <fieldset>
        <craft-select-rich name="typeId" label="Entry Type">
          <craft-option choice-value="1">Article</craft-option>
          <craft-option choice-value="2" checked>News</craft-option>
          <craft-option choice-value="3">Event</craft-option>
        </craft-select-rich>
      </fieldset>
    </form>`;
  const select = document.querySelector<CraftSelectRich>('craft-select-rich')!;
  await select.registrationComplete;
  await select.updateComplete;

  return {form: document.querySelector('form')!, select};
}

it('posts the checked option through a native input', async () => {
  const {form, select} = await formFixture();

  expect(new FormData(form).getAll('typeId')).toEqual(['2']);
  expect(select.form).toBe(form);
  // jQuery's serialize() and serializeFormInputs() only see native controls.
  expect(
    form.querySelector('input[type="hidden"][name="typeId"]')
  ).toBeTruthy();
});

it('posts a newly picked option and fires change', async () => {
  const {form, select} = await formFixture();
  const events: string[] = [];
  form.addEventListener('change', () => events.push('change'));
  form.addEventListener('input', () => events.push('input'));

  await userEvent.click(select.querySelector('lion-select-invoker')!);
  await userEvent.click(select.querySelectorAll('craft-option')[2]!);
  await select.updateComplete;

  expect(new FormData(form).get('typeId')).toBe('3');
  expect(events).toEqual(['input', 'change']);
});

it('doesn’t fire change for programmatic selection', async () => {
  const {form, select} = await formFixture();
  const onChange = () => {
    throw new Error('change fired');
  };
  form.addEventListener('change', onChange);

  select.querySelectorAll('craft-option')[0]!.checked = true;
  await select.updateComplete;

  expect(new FormData(form).get('typeId')).toBe('1');
});

it('goes back to its initial option on form reset', async () => {
  const {form, select} = await formFixture();

  select.querySelectorAll('craft-option')[0]!.checked = true;
  await select.updateComplete;
  form.reset();
  await select.updateComplete;

  expect(select.modelValue).toBe('2');
  expect(new FormData(form).get('typeId')).toBe('2');
});

it('posts nothing while disabled, including by a disabled fieldset', async () => {
  const {form, select} = await formFixture();

  select.disabled = true;
  await select.updateComplete;
  expect(new FormData(form).has('typeId')).toBe(false);

  select.disabled = false;
  await select.updateComplete;
  form.querySelector('fieldset')!.disabled = true;
  await select.updateComplete;
  expect(select.disabled).toBe(true);
  expect(new FormData(form).has('typeId')).toBe(false);
});

it('marks the invoker invalid from validation, unless the host says otherwise', async () => {
  const select = document.createElement('craft-select-rich');
  select.label = 'Status';
  select.hasNoDefaultSelected = true;
  select.innerHTML = `<craft-option choice-value="all">All</craft-option><craft-option choice-value="live">Live</craft-option>`;
  document.body.append(select);
  await select.registrationComplete;
  await select.updateComplete;
  const invoker = select.querySelector('lion-select-invoker')!;

  expect(invoker.getAttribute('aria-invalid')).toBe('false');

  select.setAttribute('aria-invalid', 'true');
  await select.updateComplete;
  expect(invoker.getAttribute('aria-invalid')).toBe('true');

  select.removeAttribute('aria-invalid');
  select.validators = [new Required()];
  select.submitted = true;
  await select.updateComplete;
  await select.updateComplete;
  expect(invoker.getAttribute('aria-invalid')).toBe('true');

  select.setAttribute('aria-invalid', 'false');
  await select.updateComplete;
  expect(invoker.getAttribute('aria-invalid')).toBe('false');

  select.removeAttribute('aria-invalid');
  await select.updateComplete;
  expect(invoker.getAttribute('aria-invalid')).toBe('true');
});
