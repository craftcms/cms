import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftActionMenu from './action-menu.js';
import './action-menu.js';
import '../action-item/action-item.js';

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
