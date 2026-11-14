import {beforeAll, expect, it, vi} from 'vite-plus/test';
import {installNavigationProgress} from './progress';

beforeAll(() => {
  installNavigationProgress();
});

/** How far the bar is filled, as it's drawn. */
function filled(): number {
  const fill = document.querySelector('.cp-navigation-progress__fill')!;

  return new DOMMatrix(getComputedStyle(fill).transform).a;
}

/** Fires an Inertia router event, as the router does on the document. */
function fire(type: 'start' | 'finish', visit: object): void {
  document.dispatchEvent(new CustomEvent(`inertia:${type}`, {detail: {visit}}));
}

const wait = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

it('shows a visit that runs past the delay, and empties once it finishes', async () => {
  const visit = {showProgress: true};

  fire('start', visit);
  expect(filled()).toBe(0);

  await vi.waitFor(() => expect(filled()).toBeGreaterThan(0));

  fire('finish', Object.assign(visit, {completed: true}));

  await vi.waitFor(() => expect(filled()).toBe(0));
});

it('leaves silent visits alone, and carries on through an interrupted one', async () => {
  fire('start', {showProgress: false});
  await wait(400);
  expect(filled()).toBe(0);

  // The replaced visit finishes after its replacement has started.
  const replaced = {showProgress: true};
  const replacement = {showProgress: true};
  fire('start', replaced);
  fire('start', replacement);
  fire('finish', Object.assign(replaced, {interrupted: true}));

  await vi.waitFor(() => expect(filled()).toBeGreaterThan(0));

  fire('finish', Object.assign(replacement, {completed: true}));
  await vi.waitFor(() => expect(filled()).toBe(0));
});
