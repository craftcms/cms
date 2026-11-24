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
    indexBody: {props: {}},
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

async function openModal(indexHeight: string) {
  const modal = document.createElement(
    'craft-element-selector-modal'
  ) as CraftElementSelectorModal;
  const index = document.createElement('div');
  index.style.blockSize = indexHeight;
  modal.append(index);
  modal.controller = fakeController();
  document.body.append(modal);
  await modal.updateComplete;
  await frames(2);
  const surface = modal.shadowRoot!.querySelector<HTMLElement>('.surface')!;

  return {modal, index, surface};
}

it('opens at golden-ratio proportions, within 90% of the viewport, whatever the index holds', async () => {
  const {index, surface} = await openModal('50px');
  const {width, height} = surface.getBoundingClientRect();

  expect(width).toBeCloseTo(Math.min(window.innerWidth * 0.96, 1280), 0);
  expect(height).toBeCloseTo(
    Math.min(Math.max(width / 1.618, 400), window.innerHeight * 0.9),
    0
  );

  index.style.blockSize = '3000px';
  await frames(1);
  expect(surface.getBoundingClientRect().height).toBe(height);
});

it('opens at the size the last element selector modal was resized to', async () => {
  localStorage.clear();
  const first = await openModal('50px');
  const grips = first.modal.shadowRoot!.querySelector('craft-resize-grips')!;
  const corner = grips.shadowRoot!.querySelector<HTMLElement>('.grip--corner')!;
  const {width, height} = first.surface.getBoundingClientRect();
  corner.focus();
  corner.dispatchEvent(
    new KeyboardEvent('keydown', {key: 'ArrowLeft', bubbles: true})
  );
  corner.dispatchEvent(
    new KeyboardEvent('keydown', {key: 'ArrowDown', bubbles: true})
  );
  first.modal.remove();

  const second = await openModal('50px');
  const reopened = second.surface.getBoundingClientRect();

  expect(reopened.width).toBe(Math.round(width) - 16);
  expect(reopened.height).toBe(Math.round(height) + 16);
  localStorage.clear();
});

it('follows its width after it is moved in the document', async () => {
  const modal = document.createElement(
    'craft-element-selector-modal'
  ) as CraftElementSelectorModal;
  modal.append(document.createElement('div'));
  modal.controller = fakeController();
  document.body.append(modal);
  await modal.updateComplete;
  await vi.waitFor(() => expect(modal.narrow).toBe(false));

  const target = document.createElement('div');
  document.body.append(target);
  target.append(modal);
  await modal.updateComplete;
  modal.style.setProperty('--c-dialog-inline-size', '400px');
  modal.style.setProperty('--c-dialog-min-inline-size', '400px');

  await vi.waitFor(() => expect(modal.narrow).toBe(true));
});
