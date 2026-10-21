import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import type CraftActionMenu from '../action-menu/action-menu.js';
import type CraftReorderButton from './reorder-button.js';
import './reorder-button.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function renderButton(html: string): Promise<CraftReorderButton> {
  document.body.innerHTML = html;
  const button = document.querySelector<CraftReorderButton>(
    'craft-reorder-button'
  )!;
  await button.updateComplete;
  return button;
}

function invokerOf(button: CraftReorderButton): HTMLElement {
  return button.shadowRoot!.querySelector<HTMLElement>('craft-button')!;
}

function menuOf(button: CraftReorderButton): CraftActionMenu {
  return button.shadowRoot!.querySelector<CraftActionMenu>(
    'craft-action-menu'
  )!;
}

async function openMenu(button: CraftReorderButton): Promise<void> {
  invokerOf(button).focus();
  await userEvent.keyboard('{Enter}');
  await menuOf(button).updateComplete;
}

async function chooseMoveDown(button: CraftReorderButton): Promise<void> {
  button
    .shadowRoot!.querySelector<HTMLElement>('[data-action="moveDown"]')!
    .focus();
  await userEvent.keyboard('{Enter}');
  await menuOf(button).updateComplete;
}

it('keeps focus on the invoker after a move is chosen with Enter', async () => {
  const button = await renderButton(
    '<craft-reorder-button position="middle"></craft-reorder-button>'
  );

  await openMenu(button);
  await chooseMoveDown(button);

  expect(menuOf(button).opened).toBe(false);
  expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));
});

it('keeps focus on the invoker when its list disables the chosen move', async () => {
  const button = await renderButton(
    '<craft-reorder-button position="first"></craft-reorder-button>'
  );
  button.addEventListener('craft-reorder', () => {
    button.position = 'last';
  });

  await openMenu(button);
  await chooseMoveDown(button);

  expect(menuOf(button).opened).toBe(false);
  expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));
});

it('returns focus to the invoker when Escape closes the menu', async () => {
  const button = await renderButton(
    '<craft-reorder-button position="middle"></craft-reorder-button>'
  );

  await openMenu(button);
  button
    .shadowRoot!.querySelector<HTMLElement>('[data-action="moveDown"]')!
    .focus();
  await userEvent.keyboard('{Escape}');
  await menuOf(button).updateComplete;

  expect(menuOf(button).opened).toBe(false);
  expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));
});

it('keeps focus on the invoker when its list moves it', async () => {
  const button = await renderButton(
    '<ul><li id="previous">Previous</li><li id="moved"><craft-reorder-button></craft-reorder-button></li></ul>'
  );

  invokerOf(button).focus();
  document
    .getElementById('previous')!
    .before(document.getElementById('moved')!);

  expect(document.activeElement).toBe(button);
  expect(button.shadowRoot!.activeElement).toBe(invokerOf(button));
});

it('leaves focus alone when it is removed and added back later', async () => {
  const button = await renderButton(
    '<ul><li id="item"><craft-reorder-button></craft-reorder-button></li></ul>'
  );
  const item = document.getElementById('item')!;

  invokerOf(button).focus();
  item.remove();
  await new Promise((resolve) => setTimeout(resolve));
  document.querySelector('ul')!.append(item);

  expect(document.activeElement).toBe(document.body);
});
