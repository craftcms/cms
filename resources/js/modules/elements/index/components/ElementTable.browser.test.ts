import '../../../../../css/cp.css';
import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import ElementTable from './ElementTable.vue';
import {useTableRowSelection} from '@/common/composables/useTableRowSelection';
import {createSampleTable} from '@/modules/elements/fixtures/elements';

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {readOnly: false}}),
}));

let app: App | undefined;
let container: HTMLElement | undefined;

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
});

afterEach(() => {
  app?.unmount();
  container?.remove();
  vi.unstubAllGlobals();
});

it('gives the title column the rest of the row when it’s the only column', async () => {
  const table = createSampleTable();
  table.setColumnVisibility({status: false, section: false, postDate: false});
  container = document.createElement('div');
  container.style.width = '1000px';
  document.body.append(container);
  app = createApp({
    render: () =>
      h(ElementTable, {
        table,
        selection: useTableRowSelection(table, {
          selectable: true,
          readOnly: false,
        }),
        selectable: true,
        structure: true,
        reorderable: true,
      } as any),
  });
  app.mount(container);
  await nextTick();

  const row = container.querySelector('tbody tr.cp-table-row')!;
  const width = (selector: string) =>
    row.querySelector(selector)!.getBoundingClientRect().width;

  expect(width('.cp-table-cell--title')).toBeGreaterThan(800);
  expect(width('.cp-table-cell--structure-reorder')).toBeLessThan(80);
  expect(width('.cp-table-cell--select')).toBeLessThan(80);
});
