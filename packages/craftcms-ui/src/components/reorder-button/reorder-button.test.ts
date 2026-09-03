import {expect, it, vi} from 'vite-plus/test';
import type CraftReorderButton from './reorder-button.js';
import './reorder-button.js';

it('emits composed reorder events to list owners', async () => {
  const list = document.createElement('div');
  const button = document.createElement(
    'craft-reorder-button'
  ) as CraftReorderButton;
  const reordered = vi.fn();
  list.addEventListener('craft-reorder', reordered);
  list.append(button);
  document.body.append(list);
  await button.updateComplete;

  button.shadowRoot
    ?.querySelector<HTMLElement>('[data-action="moveDown"]')
    ?.click();

  expect(reordered).toHaveBeenCalledOnce();
  expect(reordered.mock.calls[0]![0]).toMatchObject({
    bubbles: true,
    composed: true,
    detail: {direction: 'down'},
  });
});
