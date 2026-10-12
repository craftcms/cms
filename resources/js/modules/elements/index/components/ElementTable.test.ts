import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import DataTable from './ElementTable.vue';
import {useTableRowSelection} from '@/common/composables/useTableRowSelection';
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
      render: () =>
        h(DataTable, {
          table,
          selection: useTableRowSelection(table, {
            selectable: true,
            readOnly: false,
          }),
          selectable: true,
          ...props,
        } as any),
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

  function sortButton(root: Element, label: string): HTMLButtonElement | null {
    const header = [...root.querySelectorAll('th')].find((th) =>
      th.textContent?.includes(label)
    );
    return header?.querySelector('button') ?? null;
  }

  it('flushes the row edges with its container', () => {
    const {root} = mount();
    expect(
      root.querySelector('table')?.classList.contains('cp-table--flush')
    ).toBe(true);
  });

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

  it("labels each row's checkbox with that row's own title, Untitled included", () => {
    const {root} = mount({
      table: createSampleTable({
        data: [
          {
            id: 1,
            title: 'Welcome to Craft 6',
            status: 'live',
            section: 'Blog',
            postDate: '2026-07-06',
            label: 'Welcome to Craft 6',
          },
          {
            id: 2,
            title: '',
            status: 'live',
            section: 'Blog',
            postDate: '2026-07-05',
            label: 'Untitled entry',
          },
        ],
      }),
    });

    const labels = rows(root).map(
      (row) =>
        row.querySelector('craft-checkbox label[slot="label"]')?.textContent
    );

    expect(labels).toEqual([
      'Select Welcome to Craft 6',
      'Select Untitled entry',
    ]);
  });

  it('leads structure rows with the toggle, then the handle, then the checkbox', () => {
    const {root} = mount({structure: true, reorderable: true});

    const leading = (cells: Element[]) =>
      cells
        .slice(0, 3)
        .map((cell) =>
          cell.classList.contains('cp-table-cell--structure')
            ? 'toggle'
            : cell.querySelector('craft-reorder-button') ||
                cell.classList.contains('cell--header')
              ? 'handle'
              : cell.classList.contains('cp-table-cell--select')
                ? 'select'
                : 'other'
        );

    expect(leading([...root.querySelectorAll('thead tr > th')])).toEqual([
      'toggle',
      'handle',
      'select',
    ]);
    expect(leading([...rows(root)[0]!.children])).toEqual([
      'toggle',
      'handle',
      'select',
    ]);
  });

  it('honors handled row interactions without changing selection', async () => {
    const onClick = vi.fn(() => true);
    const onKeydown = vi.fn(() => true);
    const {root} = mount({
      itemBehavior: {
        attrs: (item: {id: number}) => ({'data-item-id': item.id}),
        onClick,
        onKeydown,
      },
    });
    const row = rows(root)[0]!;

    row.click();
    row.dispatchEvent(
      new KeyboardEvent('keydown', {
        key: ' ',
        bubbles: true,
        cancelable: true,
      })
    );
    await nextTick();

    expect(row.dataset.itemId).toBe('1');
    expect(onClick).toHaveBeenCalledWith(
      expect.objectContaining({id: 1}),
      expect.any(MouseEvent)
    );
    expect(onKeydown).toHaveBeenCalledWith(
      expect.objectContaining({id: 1}),
      expect.any(KeyboardEvent)
    );
    expect(selected(root)).toEqual([]);
  });

  it('keeps selection and sort controls in place but inert while interactions are disabled', async () => {
    const {root} = mount({interactionsDisabled: true});
    const row = rows(root)[0]!;
    const controls = [
      ...root.querySelectorAll<HTMLElement & {disabled: boolean}>(
        'tbody craft-checkbox, th button'
      ),
    ];

    row.click();
    row.dispatchEvent(
      new KeyboardEvent('keydown', {key: ' ', bubbles: true, cancelable: true})
    );
    await nextTick();

    expect(root.querySelectorAll('th button').length).toBeGreaterThan(0);
    expect(root.querySelectorAll('tbody craft-checkbox')).toHaveLength(
      rows(root).length
    );
    expect(controls.every((control) => control.disabled)).toBe(true);
    expect(selected(root)).toEqual([]);
  });

  it('leaves keyboard events from a row checkbox to the checkbox', async () => {
    const onKeydown = vi.fn(() => true);
    const {root} = mount({itemBehavior: {onKeydown}});
    const checkbox = rows(root)[0]!.querySelector('craft-checkbox')!;

    checkbox.dispatchEvent(
      new KeyboardEvent('keydown', {
        key: ' ',
        bubbles: true,
        cancelable: true,
      })
    );
    await nextTick();

    expect(onKeydown).not.toHaveBeenCalled();
    expect(selected(root)).toEqual([]);
  });

  it('moves focus to the spinner while re-sorting reloads, then back to the same sort button', async () => {
    const table = createSampleTable();
    const loading = ref(false);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(DataTable, {
          table,
          selection: useTableRowSelection(table, {
            selectable: false,
            readOnly: false,
          }),
          loading: loading.value,
        } as never),
    });
    app.mount(container);
    await nextTick();

    const titleButton = sortButton(container, 'Title')!;
    titleButton.focus();
    titleButton.click();
    expect(document.activeElement).toBe(titleButton);

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

    const restoredButton = sortButton(container, 'Title');
    expect(restoredButton).not.toBeNull();
    expect(document.activeElement).toBe(restoredButton);
  });

  it('leaves focus alone when loading toggles without a preceding sort', async () => {
    const table = createSampleTable();
    const loading = ref(false);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(DataTable, {
          table,
          selection: useTableRowSelection(table, {
            selectable: false,
            readOnly: false,
          }),
          loading: loading.value,
        } as never),
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

  it('returns focus to the sorted table when another table shares its columns', async () => {
    const firstTable = createSampleTable();
    const secondTable = createSampleTable();
    const loading = ref(false);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h('div', [
          h('section', {class: 'first'}, [
            h(DataTable, {
              table: firstTable,
              selection: useTableRowSelection(firstTable, {
                selectable: false,
                readOnly: false,
              }),
            } as never),
          ]),
          h('section', {class: 'second'}, [
            h(DataTable, {
              table: secondTable,
              selection: useTableRowSelection(secondTable, {
                selectable: false,
                readOnly: false,
              }),
              loading: loading.value,
            } as never),
          ]),
        ]),
    });
    app.mount(container);
    await nextTick();

    const second = container.querySelector('.second')!;
    sortButton(second, 'Title')!.focus();

    loading.value = true;
    await nextTick();
    await nextTick();
    loading.value = false;
    await nextTick();
    await nextTick();

    expect(document.activeElement).toBe(sortButton(second, 'Title'));
  });
});
