import {afterEach, beforeEach, describe, expect, it} from 'vite-plus/test';
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

describe('searchable', () => {
  async function searchable(): Promise<CraftSelectRich> {
    document.body.innerHTML = `
      <craft-select-rich label="Column" searchable>
        <craft-option choice-value="title">Title</craft-option>
        <craft-option choice-value="slug" hint="my-first-post">Slug<span slot="suffix">Title-like</span></craft-option>
        <craft-option choice-value="body" data-keywords="content">Body</craft-option>
      </craft-select-rich>`;
    const select =
      document.querySelector<CraftSelectRich>('craft-select-rich')!;
    await select.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));

    return select;
  }

  const visible = (select: CraftSelectRich) =>
    Array.from(select.querySelectorAll('craft-option'))
      .filter((option) => option.checkVisibility())
      .map((option) => option.textContent?.trim());

  async function open(select: CraftSelectRich): Promise<HTMLInputElement> {
    await userEvent.click(select.querySelector('lion-select-invoker')!);
    await select.updateComplete;

    return select.querySelector<HTMLInputElement>('[slot="search"] input')!;
  }

  it('focuses the search field on open', async () => {
    const select = await searchable();
    const input = await open(select);

    expect(document.activeElement).toBe(input);
  });

  it('filters the options by their text and keywords', async () => {
    const select = await searchable();
    await open(select);

    await userEvent.keyboard('sl');
    expect(visible(select)).toEqual(['SlugTitle-like']);

    await userEvent.keyboard('{Backspace}{Backspace}content');
    expect(visible(select)).toEqual(['Body']);
  });

  it('matches hints, but not slotted extras', async () => {
    const select = await searchable();
    await open(select);

    await userEvent.keyboard('first-post');
    expect(visible(select)).toEqual(['SlugTitle-like']);

    await userEvent.keyboard('{Control>}a{/Control}{Backspace}like');
    expect(visible(select)).toEqual([]);
  });

  it('says so when nothing matches', async () => {
    const select = await searchable();
    await open(select);

    await userEvent.keyboard('zzz');

    expect(visible(select)).toEqual([]);
    expect(
      select.querySelector('.select-rich__no-results')!.checkVisibility()
    ).toBe(true);
  });

  it('picks the highlighted match with Enter', async () => {
    const select = await searchable();
    await open(select);

    await userEvent.keyboard('o');
    await userEvent.keyboard('{ArrowDown}{Enter}');

    // "o" leaves Body and Slug, with Slug first highlighted, then Body.
    expect(select.modelValue).toBe('body');
    expect(select.opened).toBe(false);
  });

  it('stays closed after Enter picks a match, however long the key is held', async () => {
    const select = await searchable();
    await open(select);

    await userEvent.keyboard('{Enter>}');
    await new Promise((resolve) => setTimeout(resolve, 300));
    await userEvent.keyboard('{/Enter}');

    expect(select.opened).toBe(false);
  });

  it('clears the search with Escape before closing', async () => {
    const select = await searchable();
    const input = await open(select);

    await userEvent.keyboard('sl{Escape}');
    expect(input.value).toBe('');
    expect(select.opened).toBe(true);
    expect(visible(select)).toHaveLength(3);

    await userEvent.keyboard('{Escape}');
    expect(select.opened).toBe(false);
  });

  // A table resets its cell inputs' padding, which would run the text over the search icon.
  it('leaves room for the search icon inside a table', async () => {
    document.body.innerHTML = `
      <craft-input-search size="small"></craft-input-search>
      <table class="cp-table cp-table--editable"><tbody><tr><td>
        <craft-select-rich label="Column" searchable>
          <craft-option choice-value="title">Title</craft-option>
        </craft-select-rich>
      </td></tr></tbody></table>`;
    const select =
      document.querySelector<CraftSelectRich>('craft-select-rich')!;
    await select.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));
    const input = await open(select);
    const standalone = document.querySelector(
      'body > craft-input-search input'
    )!;

    expect(getComputedStyle(input).paddingInlineStart).toBe(
      getComputedStyle(standalone).paddingInlineStart
    );
  });

  it('starts unfiltered when reopened', async () => {
    const select = await searchable();
    await open(select);
    await userEvent.keyboard('sl{Tab}');

    await open(select);

    expect(visible(select)).toHaveLength(3);
  });
});
