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

it('only offers indent and outdent on nested buttons', async () => {
  const button = document.createElement(
    'craft-reorder-button'
  ) as CraftReorderButton;
  document.body.append(button);
  await button.updateComplete;

  expect(button.shadowRoot?.querySelector('[data-action="indent"]')).toBeNull();

  const reordered = vi.fn();
  button.addEventListener('craft-reorder', reordered);
  button.nested = true;
  button.canIndent = true;
  await button.updateComplete;

  button.shadowRoot
    ?.querySelector<HTMLElement>('[data-action="indent"]')
    ?.click();
  button.shadowRoot
    ?.querySelector<HTMLElement>('[data-action="outdent"]')
    ?.click();

  expect(reordered).toHaveBeenCalledOnce();
  expect(reordered.mock.calls[0]![0].detail).toEqual({direction: 'indent'});
});

it('blocks both moves for an only child', async () => {
  const button = document.createElement(
    'craft-reorder-button'
  ) as CraftReorderButton;
  button.position = 'only';
  const reordered = vi.fn();
  button.addEventListener('craft-reorder', reordered);
  document.body.append(button);
  await button.updateComplete;

  for (const action of ['moveUp', 'moveDown']) {
    button.shadowRoot
      ?.querySelector<HTMLElement>(`[data-action="${action}"]`)
      ?.click();
  }

  expect(reordered).not.toHaveBeenCalled();
});

it('lists moves after the reorder actions and reports the chosen one', async () => {
  const button = document.createElement(
    'craft-reorder-button'
  ) as CraftReorderButton;
  document.body.append(button);
  await button.updateComplete;

  expect(button.shadowRoot?.querySelector('hr')).toBeNull();

  const moved = vi.fn();
  button.addEventListener('craft-move', moved);
  button.moves = [
    {value: 'blog', label: 'Move to Blog'},
    {value: 'news', label: 'Move to News', icon: 'newspaper'},
  ];
  await button.updateComplete;

  const items = [
    ...(button.shadowRoot?.querySelectorAll('craft-action-item') ?? []),
  ];
  const labels = items.map((item) => item.textContent?.trim());

  expect(labels.slice(-2)).toEqual(['Move to Blog', 'Move to News']);

  items.at(-1)!.click();

  expect(moved).toHaveBeenCalledOnce();
  expect(moved.mock.calls[0]![0]).toMatchObject({
    bubbles: true,
    composed: true,
    detail: {value: 'news'},
  });
});
