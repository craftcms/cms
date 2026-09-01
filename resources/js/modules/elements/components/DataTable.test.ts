import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import DataTable from './DataTable.vue';
import {createSampleTable} from '@/modules/elements/fixtures/elements';

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {readOnly: false}}),
}));

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

  // CONFLICT-REVIEW: the focus tests below mount inline rather than through the
  // `mount()` helper above, because they need `loading` to stay reactive across
  // the assertions; the helper's props are spread once at render time.
  it('moves focus to the spinner while re-sorting reloads, then back to the same sort button', async () => {
    const table = createSampleTable();
    const loading = ref(false);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () => h(DataTable, {table, loading: loading.value}),
    });
    app.mount(container);
    await nextTick();

    const sortButton = container.querySelector<HTMLButtonElement>(
      '#header-title button'
    )!;
    sortButton.focus();
    sortButton.click();
    expect(document.activeElement).toBe(sortButton);

    // Simulates the reload cycle a real server-side sort causes.
    loading.value = true;
    await nextTick();
    await nextTick();

    const spinner = container.querySelector('craft-spinner');
    expect(spinner).not.toBeNull();
    expect(document.activeElement).toBe(spinner);
    // Its slotted text is the spinner's accessible name, so a screen reader
    // announces something (rather than going silent) when focus lands on it.
    expect(spinner?.textContent?.trim()).toBe('Sorting');

    // The reload finishes: the table remounts with the new data, and focus
    // returns to the same column's sort button.
    loading.value = false;
    await nextTick();
    await nextTick();

    const restoredButton = container.querySelector('#header-title button');
    expect(restoredButton).not.toBeNull();
    expect(document.activeElement).toBe(restoredButton);
  });

  it('leaves focus alone when loading toggles without a preceding sort', async () => {
    const table = createSampleTable();
    const loading = ref(false);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () => h(DataTable, {table, loading: loading.value}),
    });
    app.mount(container);
    await nextTick();

    // No sort click happened, so a loading state driven by something else
    // (a filter, pagination, …) shouldn't move focus onto the spinner.
    loading.value = true;
    await nextTick();
    await nextTick();

    const spinner = container.querySelector('craft-spinner');
    expect(document.activeElement).not.toBe(spinner);
    expect(spinner?.textContent?.trim()).toBe('Loading');
  });
});
