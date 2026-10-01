import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import ElementIndex from './ElementIndex.vue';
import {useDetachedElementIndex} from '../composables/useDetachedElementIndex';
import {indexData} from '../fixtures/indexData';
import type {ElementIndexModel} from '../types/model';

vi.mock('./ElementIndexToolbar.vue', () => ({default: {render: () => null}}));
vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
  usePage: () => ({props: {readOnly: false}}),
}));
let app: App;
let host: HTMLElement;
afterEach(() => {
  app?.unmount();
  host?.remove();
  localStorage.clear();
  vi.unstubAllGlobals();
});

it('keeps range selection across table and cards, skips disabled rows, and clears its anchor', async () => {
  vi.stubGlobal('Craft', {systemUid: 'selection', pageTrigger: 'p'});
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
  const initial = indexData(undefined, {
    data: [1, 2, 3, 4, 5].map((id) => ({
      id,
      label: `Entry ${id}`,
      title: `Entry ${id}`,
    })),
    sites: [],
    actions: [],
    viewModes: [
      {mode: 'table', title: 'Table', icon: 'table'},
      {mode: 'cards', title: 'Cards', icon: 'cards'},
    ],
  });
  let model!: ElementIndexModel;
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup() {
      model = useDetachedElementIndex({
        initial,
        fetch: async () => initial,
        enableRowSelection: (row) => row.id !== 3,
      });
      return () => h(ElementIndex, {view: model.view});
    },
  });
  app.mount(host);
  await settleControls();
  host
    .querySelectorAll('tr.cp-table-row')[1]!
    .dispatchEvent(new MouseEvent('click', {bubbles: true}));
  await nextTick();
  expect(model.view.selection.selectedIds.value).toEqual([2]);
  model.view.mode.value = 'cards';
  await nextTick();
  await Promise.resolve();
  await nextTick();
  await settleControls();
  const cards = host.querySelectorAll('li');
  expect(cards).toHaveLength(5);
  expect(
    (
      cards[2]!.querySelector('craft-checkbox') as HTMLElement & {
        disabled: boolean;
      }
    ).disabled
  ).toBe(true);
  cards[4]!.dispatchEvent(
    new MouseEvent('click', {bubbles: true, shiftKey: true})
  );
  await nextTick();
  expect(model.view.selection.selectedIds.value).toEqual([2, 4, 5]);
  expect(host.querySelector('[role="status"]')?.textContent).toContain('3');
  model.clearSelection();
  await nextTick();
  cards[0]!.dispatchEvent(
    new MouseEvent('click', {bubbles: true, shiftKey: true})
  );
  await nextTick();
  expect(model.view.selection.selectedIds.value).toEqual([1]);
});

async function settleControls() {
  await Promise.all(
    Array.from(host.querySelectorAll('craft-checkbox')).map(
      (element) =>
        (element as HTMLElement & {updateComplete: Promise<boolean>})
          .updateComplete
    )
  );
  await nextTick();
}
