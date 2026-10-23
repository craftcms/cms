import {beforeEach, expect, it, vi} from 'vite-plus/test';
import type CraftElementSelectorModal from './element-selector-modal.js';
import './element-selector-modal.js';
import type {
  ElementSelectorController,
  ElementSelectorState,
} from '@src/core/element-selector/index.js';

function fakeController(): ElementSelectorController {
  const state: ElementSelectorState = {
    open: true,
    loading: false,
    busy: false,
    selection: [],
    disabledElementIds: [],
    indexBody: {html: '', props: {}},
    error: null,
    title: 'Choose an entry',
    showTitle: false,
    selectLabel: 'Select',
    canSubmit: false,
    canCancel: true,
  };

  return {
    submit: vi.fn(),
    cancel: vi.fn(),
    state,
    on: () => () => {},
  } as unknown as ElementSelectorController;
}

const frames = (count: number) =>
  new Promise<void>((resolve) => {
    const next = (remaining: number) =>
      remaining === 0
        ? resolve()
        : requestAnimationFrame(() => next(remaining - 1));
    next(count);
  });

beforeEach(() => {
  document.body.innerHTML = '';
});

it('holds the height it opened at while the index reloads, until the window resizes', async () => {
  const modal = document.createElement(
    'craft-element-selector-modal'
  ) as CraftElementSelectorModal;
  const index = document.createElement('div');
  index.style.blockSize = '600px';
  modal.append(index);
  modal.controller = fakeController();
  document.body.append(modal);
  await modal.updateComplete;
  await frames(3);

  const surface = modal.shadowRoot!.querySelector<HTMLElement>('.surface')!;
  const height = () => surface.getBoundingClientRect().height;
  const opened = height();

  expect(modal.hasAttribute('size-locked')).toBe(true);

  // A reload swaps in a much shorter listing.
  index.style.blockSize = '50px';
  await frames(1);
  expect(height()).toBe(opened);

  window.dispatchEvent(new Event('resize'));
  expect(modal.hasAttribute('size-locked')).toBe(false);
  expect(height()).toBeLessThan(opened);

  await vi.waitFor(() => expect(modal.hasAttribute('size-locked')).toBe(true));
});
