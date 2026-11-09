import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser';
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

// Unselected options take the listbox's fill, which reads in the dark theme.
it('leaves unselected options unfilled', async () => {
  const select = await fixture();
  const option = select.querySelectorAll('craft-option')[1]!;

  expect(getComputedStyle(option).backgroundColor).toBe('rgba(0, 0, 0, 0)');
});

it('doesn’t light up the hovered invoker', async () => {
  const select = await fixture();
  const invoker = select.querySelector<HTMLElement>('craft-select-invoker')!;

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
    const listbox =
      select.shadowRoot!.querySelector<HTMLElement>('[role="listbox"]')!;
    await new Promise((resolve) => setTimeout(resolve, 300));

    const invoker = select
      .querySelector<HTMLElement>('craft-select-invoker')!
      .getBoundingClientRect();
    const list = listbox.getBoundingClientRect();

    expect(invoker.right).toBeLessThanOrEqual(
      container.getBoundingClientRect().right + 1
    );
    expect(list.left).toBeGreaterThanOrEqual(0);
    expect(list.right).toBeLessThanOrEqual(window.innerWidth);
  }
);

it('keeps its open list as wide as the field when the field widens', async () => {
  await page.viewport(400, 640);
  const select = await fixture();
  const invoker = select.querySelector<HTMLElement>('craft-select-invoker')!;
  const pane = select.shadowRoot!.querySelector<HTMLElement>(
    '#overlay-content-node-wrapper'
  )!;

  await userEvent.click(invoker);
  await expect.element(page.getByRole('listbox')).toBeVisible();
  await page.viewport(800, 640);

  await expect
    .poll(() => pane.getBoundingClientRect().width)
    .toBeGreaterThanOrEqual(invoker.getBoundingClientRect().width);
  expect(invoker.getBoundingClientRect().width).toBeGreaterThan(400);
});

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
  await select.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));

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

  await userEvent.click(select.querySelector('craft-select-invoker')!);
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

  expect(select.value).toBe('2');
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

it('is a select-only combobox that keeps focus while its listbox is open', async () => {
  document.body.innerHTML = `<craft-select-rich label="Status" help-text="Pick one">
      <craft-option choice-value="all">All</craft-option>
      <craft-option choice-value="live" checked>Live</craft-option>
      <craft-option choice-value="draft" disabled>Draft</craft-option>
    </craft-select-rich>`;
  await new Promise((r) => setTimeout(r, 100));
  const box = page.getByRole('combobox', {name: 'Status', expanded: false});
  await expect.element(box).toBeInTheDocument();
  await expect.element(box).toHaveAccessibleDescription('Pick one');
  await expect.element(box).toHaveTextContent('Live');
  await userEvent.click(box);
  await expect
    .element(page.getByRole('combobox', {name: 'Status', expanded: true}))
    .toBeInTheDocument();
  await expect
    .element(page.getByRole('listbox', {name: 'Status'}))
    .toBeInTheDocument();
  await expect
    .element(page.getByRole('option', {name: 'Live', selected: true}))
    .toBeInTheDocument();
  await expect
    .element(page.getByRole('option', {name: 'Draft', disabled: true}))
    .toBeInTheDocument();
  const invoker = document.querySelector('craft-select-invoker')!;
  const active = invoker.getAttribute('aria-activedescendant');
  expect(document.getElementById(active!)?.textContent).toBe('Live');
  await userEvent.click(page.getByRole('option', {name: 'All'}));
  expect(
    document.querySelector<CraftSelectRich>('craft-select-rich')!.value
  ).toBe('all');
  expect(document.activeElement).toBe(invoker);
});

async function keyboardFixture(): Promise<CraftSelectRich> {
  document.body.innerHTML = `
    <button type="button">Before</button>
    <craft-select-rich label="Fruit">
      <craft-option choice-value="apple">Apple</craft-option>
      <craft-option choice-value="banana" disabled>Banana</craft-option>
      <craft-option choice-value="cherry" checked>Cherry</craft-option>
      <craft-option choice-value="damson">Damson</craft-option>
    </craft-select-rich>
    <button type="button">After</button>`;
  const select = document.querySelector<CraftSelectRich>('craft-select-rich')!;
  await select.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  document.querySelector<HTMLElement>('craft-select-invoker')!.focus();

  return select;
}

function active(select: CraftSelectRich): string | undefined {
  return select.querySelector('craft-option[active]')?.textContent?.trim();
}

it.each(['{Enter}', ' ', '{ArrowDown}', '{ArrowUp}'])(
  'opens on %s with the selected option active',
  async (key) => {
    const select = await keyboardFixture();

    await userEvent.keyboard(key);

    expect(select.opened).toBe(true);
    expect(active(select)).toBe('Cherry');
  }
);

it('moves past disabled options, stops at the ends, and jumps with Home and End', async () => {
  const select = await keyboardFixture();
  await userEvent.keyboard('{Enter}');
  const visited = [];
  for (const key of [
    'ArrowUp',
    'ArrowUp',
    'ArrowUp',
    'End',
    'ArrowDown',
    'Home',
  ]) {
    await userEvent.keyboard(`{${key}}`);
    visited.push(active(select));
  }

  expect(visited).toEqual([
    'Apple',
    'Apple',
    'Apple',
    'Damson',
    'Damson',
    'Apple',
  ]);
});

it('selects with Enter, and closes without a change on Escape, keeping focus on the field', async () => {
  const select = await keyboardFixture();
  const invoker = select.querySelector('craft-select-invoker')!;
  const changes: string[] = [];
  select.addEventListener('change', () => changes.push(select.value));

  await userEvent.keyboard('{Enter}{ArrowDown}{Escape}');
  expect(select.opened).toBe(false);
  expect(select.value).toBe('cherry');

  await userEvent.keyboard('{Enter}{ArrowDown}{Enter}');
  expect(select.opened).toBe(false);
  expect(select.value).toBe('damson');
  expect(changes).toEqual(['damson']);
  expect(document.activeElement).toBe(invoker);
});

it('selects by type-ahead while closed and moves the highlight while open', async () => {
  const select = await keyboardFixture();

  await userEvent.keyboard('a');
  expect(select.value).toBe('apple');

  await userEvent.keyboard('{Enter}d');
  expect(active(select)).toBe('Damson');
  expect(select.value).toBe('apple');
});

it('closes on Tab and moves focus on', async () => {
  const select = await keyboardFixture();

  await userEvent.keyboard('{Enter}');
  await userEvent.tab();

  expect(select.opened).toBe(false);
  expect(document.activeElement).toBe(document.querySelectorAll('button')[1]);
});

it('keeps its role with a single option, read-only with nothing to open', async () => {
  document.body.innerHTML = `
    <craft-select-rich label="Site">
      <craft-option choice-value="default">Default</craft-option>
    </craft-select-rich>`;
  const select = document.querySelector<CraftSelectRich>('craft-select-rich')!;
  await select.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));

  const field = page.getByRole('combobox', {name: 'Site'});
  await expect.element(field).toHaveAttribute('aria-readonly', 'true');
  await expect.element(field).not.toHaveAttribute('aria-haspopup');
  await userEvent.click(field);
  expect(select.opened).toBe(false);
});

it('marks the field invalid while `invalid` is set', async () => {
  document.body.innerHTML = `<craft-select-rich label="Status" invalid><craft-option choice-value="all">All</craft-option></craft-select-rich>`;
  const select = document.querySelector<CraftSelectRich>('craft-select-rich')!;
  await select.updateComplete;
  const field = select.querySelector('craft-select-invoker')!;

  expect(field.getAttribute('aria-invalid')).toBe('true');

  select.invalid = false;
  await select.updateComplete;
  expect(field.hasAttribute('aria-invalid')).toBe(false);
});
