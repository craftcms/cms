import {effectScope} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {indexData} from '../fixtures/indexData';
import {useElementIndexPage} from './useElementIndexPage';

// `readOnly` is the global "admin changes are disallowed" flag. Element
// content isn't an admin change, so it mustn't lock the index.
vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
  usePage: () => ({props: {...indexData(1), readOnly: true}}),
  router: {on: () => () => {}, visit: vi.fn(), reload: vi.fn()},
}));

let scope: ReturnType<typeof effectScope>;

afterEach(() => {
  scope?.stop();
  vi.unstubAllGlobals();
});

it('keeps the index editable when admin changes are disallowed', () => {
  vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
  scope = effectScope();
  const index = scope.run(() =>
    useElementIndexPage({route: {url: () => '/admin/entries'} as never})
  )!;

  expect(index.view.selection.readOnly.value).toBe(false);
});
