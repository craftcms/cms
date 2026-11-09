import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser';
import {contrast} from '../../../test/contrast';
import type CraftButton from '../button/button.js';
import type CraftActionMenu from './action-menu.js';
import './action-menu.js';
import '../action-item/action-item.js';
import '../button/button.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function renderMenu(markup: string): Promise<CraftActionMenu> {
  document.body.innerHTML = markup;
  const menu = document.querySelector<CraftActionMenu>('craft-action-menu')!;
  await menu.updateComplete;
  return menu;
}

function focusedText(): string | null | undefined {
  return (document.activeElement as HTMLElement | null)?.textContent;
}

it('tells assistive tech the button expands, not that it opens a menu', async () => {
  await renderMenu(`
    <craft-action-menu>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>First</craft-action-item>
      </div>
    </craft-action-menu>
  `);
  const invoker = page.getByRole('button', {name: 'Open', expanded: false});

  await expect.element(invoker).toBeInTheDocument();
  await expect.element(invoker).not.toHaveAttribute('aria-haspopup');
});

it('keeps native roles on its items, with no menu around them', async () => {
  await renderMenu(`
    <craft-action-menu>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>Duplicate</craft-action-item>
        <craft-action-item href="#settings">Settings</craft-action-item>
      </div>
    </craft-action-menu>
  `);

  await userEvent.click(page.getByRole('button', {name: 'Open'}));

  await expect
    .element(page.getByRole('button', {name: 'Duplicate'}))
    .toBeVisible();
  await expect
    .element(page.getByRole('link', {name: 'Settings'}))
    .toBeVisible();
  await expect.element(page.getByRole('menu')).not.toBeInTheDocument();
  await expect.element(page.getByRole('menuitem')).not.toBeInTheDocument();
});

it('points the invoker at the content it controls', async () => {
  const menu = await renderMenu(`
    <craft-action-menu>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>Duplicate</craft-action-item>
      </div>
    </craft-action-menu>
  `);
  const controls = menu
    .querySelector('[slot="invoker"]')!
    .getAttribute('aria-controls');

  expect(document.getElementById(controls!)).toBe(
    menu.querySelector('[slot="content"]')
  );
});

it.each([
  [undefined, 'Actions'],
  ['Entry actions', 'Entry actions'],
])('names its generated invoker from label %s', async (label, expected) => {
  const menu = document.createElement('craft-action-menu') as CraftActionMenu;
  if (label) {
    menu.label = label;
  }
  menu.actions = [{label: 'Duplicate'}];
  document.body.append(menu);
  await menu.updateComplete;

  await expect
    .element(page.getByRole('button', {name: expected, exact: true}))
    .toBeInTheDocument();
});

describe('when disabled', () => {
  it('marks a slotted invoker disabled and stays closed', async () => {
    const menu = await renderMenu(`
      <craft-action-menu disabled>
        <craft-button slot="invoker" type="button">Open</craft-button>
        <div slot="content">
          <craft-action-item>Duplicate</craft-action-item>
        </div>
      </craft-action-menu>
    `);
    const invoker = page.getByRole('button', {name: 'Open'});

    await expect.element(invoker).toHaveAttribute('aria-disabled', 'true');

    await userEvent.click(invoker, {force: true});
    menu.querySelector<HTMLElement>('[slot="invoker"]')!.focus();
    await userEvent.keyboard('{Enter}');
    await userEvent.keyboard(' ');
    await menu.updateComplete;

    expect(menu.opened).toBe(false);
  });

  it('disables a generated invoker', async () => {
    const menu = document.createElement('craft-action-menu') as CraftActionMenu;
    menu.disabled = true;
    menu.actions = [{label: 'Duplicate'}];
    document.body.append(menu);
    await menu.updateComplete;

    await expect
      .element(page.getByRole('button', {name: 'Actions'}))
      .toBeDisabled();
  });
});

it('stays closed when an item is chosen while it is still opening', async () => {
  const menu = await renderMenu(`
    <craft-action-menu>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>First</craft-action-item>
      </div>
    </craft-action-menu>
  `);
  const invoker = menu.querySelector<CraftButton>('craft-button')!;
  const item = menu.querySelector<HTMLElement>('craft-action-item')!;
  await invoker.updateComplete;

  invoker.click();
  item.click();
  await new Promise((resolve) => setTimeout(resolve, 100));

  expect(menu.opened).toBe(false);
  expect(item.checkVisibility()).toBe(false);
});

it('closes on Escape from an item and returns focus to the invoker', async () => {
  const menu = await renderMenu(`
    <craft-action-menu>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>Duplicate</craft-action-item>
      </div>
    </craft-action-menu>
  `);
  const invoker = menu.querySelector<CraftButton>('craft-button')!;

  await userEvent.click(page.getByRole('button', {name: 'Open'}));
  await expect.poll(focusedText).toBe('Duplicate');
  await userEvent.keyboard('{Escape}');

  await expect.poll(() => menu.opened).toBe(false);
  expect(document.activeElement).toBe(invoker);
});

it('follows a link item on Space', async () => {
  await renderMenu(`
    <craft-action-menu>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item href="#space-target">Settings</craft-action-item>
      </div>
    </craft-action-menu>
  `);

  await userEvent.click(page.getByRole('button', {name: 'Open'}));
  await expect.poll(focusedText).toBe('Settings');
  await userEvent.keyboard(' ');

  await expect.poll(() => location.hash).toBe('#space-target');
  history.replaceState(null, '', location.pathname + location.search);
});

it.each([
  ['Enter', () => userEvent.keyboard('{Enter}')],
  ['Space', () => userEvent.keyboard(' ')],
  ['a click', () => userEvent.click(page.getByRole('button', {name: 'Open'}))],
])(
  'moves focus to the first item it can reach when opened with %s',
  async (_how, open) => {
    const menu = await renderMenu(`
      <craft-action-menu>
        <craft-button slot="invoker" type="button">Open</craft-button>
        <div slot="content">
          <craft-action-item hidden>One</craft-action-item>
          <craft-action-item disabled>Two</craft-action-item>
          <craft-action-item>Three</craft-action-item>
        </div>
      </craft-action-menu>
    `);
    const invoker = menu.querySelector<CraftButton>('craft-button')!;
    await invoker.updateComplete;
    invoker.focus();

    await open();

    // Hidden items are skipped; disabled ones stay reachable, as with the arrow keys.
    await expect.poll(focusedText).toBe('Two');
  }
);

it('moves focus to the search field when a searchable menu opens', async () => {
  await renderMenu(`
    <craft-action-menu searchable>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>First</craft-action-item>
      </div>
    </craft-action-menu>
  `);

  await userEvent.click(page.getByRole('button', {name: 'Open'}));

  await expect
    .element(page.getByRole('textbox', {name: 'Search'}))
    .toHaveFocus();
});

it('closes when Tab moves focus past its last item', async () => {
  const menu = await renderMenu(`
    <craft-action-menu>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>Duplicate</craft-action-item>
      </div>
    </craft-action-menu>
    <button type="button">Next</button>
  `);

  await userEvent.click(page.getByRole('button', {name: 'Open'}));
  await expect.poll(focusedText).toBe('Duplicate');
  await userEvent.keyboard('{Tab}');

  await expect.poll(() => menu.opened).toBe(false);
  await expect.element(page.getByRole('button', {name: 'Next'})).toHaveFocus();
});

it('keeps the search icon as the visible label while text is entered', async () => {
  const menu = await renderMenu(`
    <craft-action-menu searchable>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>First</craft-action-item>
      </div>
    </craft-action-menu>
  `);

  await userEvent.click(page.getByRole('button', {name: 'Open'}));
  const search = page.getByRole('textbox', {name: 'Search'});
  await expect.element(search).toHaveFocus();
  await userEvent.keyboard('Fi');

  await expect.element(search).toHaveValue('Fi');
  await expect
    .element(
      menu.querySelector<HTMLElement>(
        'craft-input-search > craft-icon[slot="prefix"][name="search"]'
      )!
    )
    .toBeVisible();
});

it('keeps items within the popover when their content is wide', async () => {
  await import('../../styles/cp.css');
  const menu = await renderMenu(`
    <craft-action-menu>
      <button slot="invoker" type="button">Open</button>
      <div slot="content">
        <craft-action-item>${'Unbreakable '.repeat(40)}</craft-action-item>
      </div>
    </craft-action-menu>
  `);
  menu.opened = true;
  await menu.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));

  const item = menu.querySelector('craft-action-item')!;
  const content = item.parentElement!;
  const button = item.shadowRoot!.querySelector('.action-item')!;

  expect(content.getBoundingClientRect().width).toBeGreaterThan(0);
  expect(button.getBoundingClientRect().right).toBeLessThanOrEqual(
    content.getBoundingClientRect().right
  );
  expect(content.scrollWidth).toBe(content.clientWidth);
});

it('closes and returns focus to a craft-button invoker when an item is chosen with Enter', async () => {
  const menu = await renderMenu(`
    <craft-action-menu>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>First</craft-action-item>
      </div>
    </craft-action-menu>
  `);
  const invoker = menu.querySelector<CraftButton>('craft-button')!;
  await invoker.updateComplete;
  invoker.focus();
  await userEvent.keyboard('{Enter}');
  await menu.updateComplete;

  menu.querySelector<HTMLElement>('craft-action-item')!.focus();
  await userEvent.keyboard('{Enter}');
  await menu.updateComplete;

  expect(menu.opened).toBe(false);
  expect(document.activeElement).toBe(invoker);
});

it('spaces separators in slotted content, including ones added later, as it does those it renders from actions', async () => {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <craft-action-menu id="slotted">
      <button slot="invoker" type="button">Open</button>
      <div slot="content">
        <craft-action-item>First</craft-action-item>
        <hr />
        <craft-action-item>Second</craft-action-item>
      </div>
    </craft-action-menu>
    <craft-action-menu id="generated"></craft-action-menu>
  `;
  const slotted = document.querySelector<CraftActionMenu>('#slotted')!;
  const generated = document.querySelector<CraftActionMenu>('#generated')!;
  generated.actions = [
    {type: 'button', label: 'First'},
    {type: 'hr'},
    {type: 'button', label: 'Second'},
  ];
  await slotted.updateComplete;
  await generated.updateComplete;
  slotted.opened = true;
  generated.opened = true;
  await slotted.updateComplete;
  await generated.updateComplete;

  const later = document.createElement('hr');
  slotted.querySelector('[slot="content"]')!.append(later);
  await new Promise((resolve) => requestAnimationFrame(resolve));

  const spacing = (hr: Element) => {
    const style = getComputedStyle(hr);

    return [style.marginBlockStart, style.marginBlockEnd, style.marginInline];
  };
  const expected = spacing(generated.querySelector('hr')!);

  expect(expected[0]).not.toBe('0px');
  expect(spacing(slotted.querySelector('hr')!)).toEqual(expected);
  expect(spacing(later)).toEqual(expected);
});

it('leaves a flush craft-button invoker pulled in on both sides, outside a button group', async () => {
  document.body.innerHTML = `
    <craft-action-menu>
      <craft-button slot="invoker" variant="plain" size="small" icon="ellipsis" flush aria-label="Actions"></craft-button>
    </craft-action-menu>
  `;
  const invoker = document.querySelector<CraftButton>('craft-button')!;
  await invoker.updateComplete;
  const style = getComputedStyle(invoker);

  expect(parseFloat(style.marginInlineEnd)).toBeLessThan(0);
  expect(style.marginInlineStart).toBe(style.marginInlineEnd);
});

it.each(['light', 'dark'])(
  'sets the search placeholder at 4.5:1 against the menu inside a shadow root in the %s theme',
  async (theme) => {
    await import('../../styles/cp.css');
    document.body.innerHTML = `<div data-theme="${theme}"><div></div></div>`;
    const shadowRoot = document
      .querySelector('[data-theme] > div')!
      .attachShadow({mode: 'open'});
    shadowRoot.innerHTML = `
      <craft-action-menu searchable>
        <button slot="invoker" type="button">Open</button>
        <div slot="content">
          <craft-action-item>First</craft-action-item>
        </div>
      </craft-action-menu>
    `;
    const menu =
      shadowRoot.querySelector<CraftActionMenu>('craft-action-menu')!;
    await menu.updateComplete;
    menu.opened = true;
    await menu.updateComplete;

    const input = menu.querySelector<HTMLInputElement>(
      '.action-menu__search input'
    )!;
    const placeholder = getComputedStyle(input, '::placeholder');
    const surface = getComputedStyle(
      document.querySelector('[data-theme]')!
    ).getPropertyValue('--c-surface-overlay');

    expect(placeholder.opacity).toBe('1');
    expect(contrast(placeholder.color, surface)).toBeGreaterThanOrEqual(4.5);
  }
);
