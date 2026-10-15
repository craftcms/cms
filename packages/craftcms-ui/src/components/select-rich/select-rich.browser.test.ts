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
