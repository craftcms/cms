import {expect, it, vi} from 'vite-plus/test';
import type CraftReorderButton from './reorder-button.js';
import {getReorderActions, getReorderPosition} from './reorder-button.js';

it('describes vertical reorder actions at a boundary', () => {
  expect(getReorderActions('vertical', 'first')).toEqual([
    {
      direction: 'up',
      icon: 'arrow-up',
      label: 'Move up',
      disabled: true,
    },
    {
      direction: 'down',
      icon: 'arrow-down',
      label: 'Move down',
      disabled: false,
    },
  ]);
});

it('describes RTL horizontal reorder actions', () => {
  expect(getReorderActions('horizontal', 'last', true)).toEqual([
    {
      direction: 'up',
      icon: 'arrow-right',
      label: 'Move forward',
      disabled: false,
    },
    {
      direction: 'down',
      icon: 'arrow-left',
      label: 'Move backward',
      disabled: true,
    },
  ]);
});

it('derives reorder positions from list indexes', () => {
  expect([0, 1, 2].map((index) => getReorderPosition(index, 3))).toEqual([
    'first',
    'middle',
    'last',
  ]);
  expect(getReorderPosition(0, 1)).toBe('only');
  expect(getReorderActions('vertical', 'only')).toEqual([
    expect.objectContaining({direction: 'up', disabled: true}),
    expect.objectContaining({direction: 'down', disabled: true}),
  ]);
});

it('emits composed reorder events to list owners', async () => {
  const list = document.createElement('div');
  const button = document.createElement(
    'craft-reorder-button'
  ) as CraftReorderButton;
  const reordered = vi.fn();
  list.addEventListener('reorder', reordered);
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
