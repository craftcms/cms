import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import {contrast} from '../../../test/contrast';
import type CraftButton from '../button/button.js';
import type CraftActionMenu from './action-menu.js';
import './action-menu.js';
import '../action-item/action-item.js';
import '../button/button.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('keeps items within the popover when their content is wide', async () => {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <craft-action-menu>
      <button slot="invoker" type="button">Open</button>
      <div slot="content">
        <craft-action-item>${'Unbreakable '.repeat(40)}</craft-action-item>
      </div>
    </craft-action-menu>
  `;
  const menu = document.querySelector<CraftActionMenu>('craft-action-menu')!;
  await menu.updateComplete;
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
  document.body.innerHTML = `
    <craft-action-menu>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content">
        <craft-action-item>First</craft-action-item>
      </div>
    </craft-action-menu>
  `;
  const menu = document.querySelector<CraftActionMenu>('craft-action-menu')!;
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
