import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import DataTable from './DataTable.vue';
import {createSampleTable} from '@/modules/elements/fixtures/elements';

describe('DataTable', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  beforeEach(() => {
    // Cell content pulls in `<craft-icon>`s, which fetch their SVGs; left in
    // flight they're aborted at teardown and reported as unhandled errors.
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

  function mount(props: Record<string, unknown> = {}) {
    const table = createSampleTable();

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () => h(DataTable, {table, selectable: true, ...props} as any),
    });
    app.mount(container);

    return {root: container, table};
  }

  function rows(root: HTMLElement): HTMLElement[] {
    return [...root.querySelectorAll<HTMLElement>('tr.cp-table-row')];
  }

  function selected(root: HTMLElement): string[] {
    return rows(root)
      .filter((row) => row.classList.contains('sel'))
      .map((row) => row.textContent?.trim().slice(0, 20) ?? '');
  }

  it('marks no row selected to begin with', () => {
    const {root} = mount();

    expect(rows(root).length).toBeGreaterThan(0);
    expect(selected(root)).toEqual([]);
  });

  // The row carries the same `sel` class the card and thumb bodies use, so the
  // selected styling can match across every view mode.
  it('marks a selected row with the shared selected class', async () => {
    const {root, table} = mount();

    table.getRowModel().rows[1]!.toggleSelected(true);
    await nextTick();

    expect(rows(root).map((row) => row.classList.contains('sel'))).toEqual([
      false,
      true,
      ...rows(root)
        .slice(2)
        .map(() => false),
    ]);
  });

  it('drops the class again when the row is deselected', async () => {
    const {root, table} = mount();
    const row = table.getRowModel().rows[0]!;

    row.toggleSelected(true);
    await nextTick();
    expect(selected(root)).toHaveLength(1);

    row.toggleSelected(false);
    await nextTick();
    expect(selected(root)).toEqual([]);
  });

  it('marks every row when the whole table is selected', async () => {
    const {root, table} = mount();

    table.toggleAllRowsSelected(true);
    await nextTick();

    expect(selected(root)).toHaveLength(rows(root).length);
  });
});
