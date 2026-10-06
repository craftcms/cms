import {createApp, h, nextTick, reactive, ref, type Slots} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {Table} from '@tanstack/vue-table';
import {
  type CraftTableFeatures,
  useCraftTable,
} from '@/modules/admin-table/craftTable';
import {useElementIndexSelection} from '../composables/useElementIndexSelection';
import {useNavItemActions} from '@/common/composables/useNavItemActions';

const page = vi.hoisted(() => ({
  elementIndex: null as unknown,
}));
const quickEdit = vi.hoisted(() => ({
  onDblClick: vi.fn(),
  openEditor: vi.fn(),
}));
vi.mock('@/modules/elements/index/composables/useElementIndexPage', () => ({
  useElementIndexPage: () => page.elementIndex,
}));
vi.mock('@/modules/elements/composables/useElementQuickEdit', () => ({
  useElementQuickEdit: () => quickEdit,
}));
vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
  router: {visit: vi.fn(), prefetch: vi.fn()},
  usePage: () => ({props: {readOnly: false}}),
}));
// Customize Sources is for admins, where admin changes are allowed.
const craft = vi.hoisted(() => ({admin: true, allowAdminChanges: true}));

vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({
    site: {handle: 'default'},
    currentUser: {value: {admin: craft.admin}},
    allowAdminChanges: {value: craft.allowAdminChanges},
  }),
}));

// Rendered inline rather than teleported: there's no screen shell here to
// teleport into, and the shell isn't what's under test.
vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: {
    name: 'LayoutSlot',
    setup:
      (_: unknown, {slots}: {slots: Record<string, () => unknown>}) =>
      () =>
        slots.default?.(),
  },
}));

// The list itself isn't what's under test, and it pulls in a real table.
const stub = {default: {name: 'Stub', render: () => null}};

vi.mock('@/modules/elements/index/components/ElementTable.vue', () => ({
  default: {
    name: 'ElementTable',
    props: {table: Object},
    render(this: {table: Table<CraftTableFeatures, Record<string, unknown>>}) {
      const row = this.table.getRowModel().rows[0]?.original as
        | {cpEditUrl?: string | null; label?: string}
        | undefined;

      return row?.cpEditUrl
        ? h('a', {href: row.cpEditUrl}, row.label ?? 'Element')
        : null;
    },
  },
}));
vi.mock('@/modules/elements/components/ElementCards.vue', () => stub);
vi.mock('@/modules/elements/index/components/ElementIndexToolbar.vue', () => ({
  default: {
    name: 'ElementIndexToolbar',
    setup:
      (_: unknown, {slots}: {slots: Slots}) =>
      () =>
        h('div', slots.actions?.()),
  },
}));
vi.mock('@/modules/elements/components/ElementThumbs.vue', () => stub);
// Records whether it's open — which is all a gear in the nav can do to it.
const modal = vi.hoisted(() => ({isActive: false}));

vi.mock(
  '@/modules/elements/index/components/customize-sources/CustomizeSourcesModal.vue',
  () => ({
    default: {
      name: 'CustomizeSourcesModal',
      props: {isActive: Boolean},
      render(this: {isActive: boolean}) {
        modal.isActive = this.isActive;

        return null;
      },
    },
  })
);

const ElementIndexPage = (await import('./ElementIndexPage.vue')).default;

let teardown: (() => void) | undefined;

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
});

afterEach(() => {
  teardown?.();
  teardown = undefined;
  modal.isActive = false;
  quickEdit.onDblClick.mockReset();
  quickEdit.openEditor.mockReset();
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

async function mountPage(
  props: Record<string, unknown> = {},
  data: Array<Record<string, unknown>> = [
    {
      id: 11,
      label: 'Alpha',
      cpEditUrl: '/admin/entries/11',
      viewUrl: 'https://example.test/alpha',
      url: '/admin/entries/11',
      inlineEditable: true,
    },
  ],
  mode = 'table'
) {
  const viewState = reactive({mode});
  const elementTable = useCraftTable({
    data,
    columns: [],
    getRowId: (row) => String(row.id),
    state: {
      rowSelection: {'11': true},
      pagination: {pageIndex: 0, pageSize: 50},
    },
  });

  page.elementIndex = {
    clearSelection: vi.fn(),
    refresh: vi.fn(),
    view: {
      selection: useElementIndexSelection(elementTable, {
        selectable: true,
        readOnly: false,
      }),
      elementIndex: reactive({
        sources: [
          {type: 'native', key: '*', label: 'All entries'},
          {type: 'native', key: 'section:blog', label: 'Blog'},
        ],
        source: {key: '*'},
        pagination: {from: 1, to: data.length, total: data.length},
        actions: ['edit', 'view'].map((type) => ({
          key: type,
          label: type === 'edit' ? 'Edit' : 'View',
          bulk: false,
          action: {type: 'event', name: `craft:${type}-element`},
        })),
        elementType: 'entry',
        context: 'index',
        viewModes: [],
        statusOptions: [],
        data,
        exporters: [{type: 'Expanded', name: 'Expanded', formattable: true}],
        sites: [],
      }),
      table: elementTable,
      data: ref(data),
      viewState,
      conditions: ref({}),
      search: ref(''),
      status: ref(''),
      submit: vi.fn(),
      columnOptions: ref([]),
      tableColumns: ref([]),
      reorder: vi.fn(),
      sortField: ref(null),
      sortDirection: ref(null),
      mode: ref(mode),
      inlineEditing: {
        active: ref(false),
        load: vi.fn(),
        save: vi.fn(),
      },
      exportElements: vi.fn(),
      structureView: {isCollapsed: () => false, isPending: () => false},
      loading: ref(false),
      processing: ref(false),
      filterContext: ref(null),
      visibleViewModes: ref([]),
    },
  };

  const container = document.createElement('div');
  document.body.append(container);

  const app = createApp({
    render: () =>
      h(ElementIndexPage, {
        route: {url: () => '/admin/entries'} as never,
        ...props,
      }),
  });

  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  teardown = () => {
    app.unmount();
    container.remove();
  };
  await nextTick();

  return container;
}

function clickAction(container: HTMLElement, label: string) {
  const item = Array.from(container.querySelectorAll('craft-action-item')).find(
    (item) => item.textContent?.trim() === label
  );
  expect(item).toBeDefined();
  item!.click();
}

it('passes the Actions invoker to the bulk Edit slideout', async () => {
  const container = await mountPage();

  clickAction(container, 'Edit');

  expect(quickEdit.openEditor).toHaveBeenCalledWith(
    '/admin/entries/11',
    expect.any(HTMLElement)
  );
  expect(quickEdit.openEditor.mock.calls[0]![1].textContent).toContain(
    'Actions'
  );
});

it('leaves the primary title link to page navigation', async () => {
  const container = await mountPage();
  const link = container.querySelector<HTMLAnchorElement>(
    'a[href="/admin/entries/11"]'
  )!;

  const followsLink = link.dispatchEvent(
    new MouseEvent('click', {bubbles: true, cancelable: true})
  );

  expect(followsLink).toBe(true);
  expect(quickEdit.openEditor).not.toHaveBeenCalled();
});

it('offers inline editing and export on page tables', async () => {
  const container = await mountPage();

  expect(container.textContent).toContain('Edit inline');
  expect(container.textContent).toContain('Export');
});

it('hides inline editing outside table mode', async () => {
  const container = await mountPage({}, undefined, 'cards');

  expect(container.textContent).not.toContain('Edit inline');
  expect(container.textContent).toContain('Export');
});

it('opens the public URL instead of the CP URL in a new tab', async () => {
  const open = vi.spyOn(window, 'open').mockImplementation(() => null);
  const container = await mountPage({}, undefined, 'thumbs');

  clickAction(container, 'View');

  expect(open).toHaveBeenCalledWith(
    'https://example.test/alpha',
    '_blank',
    'noopener'
  );
});

it('does nothing when View has no public URL', async () => {
  const open = vi.spyOn(window, 'open').mockImplementation(() => null);
  const container = await mountPage(
    {},
    [
      {
        id: 11,
        label: 'Alpha',
        url: '/admin/entries/11',
        cpEditUrl: '/admin/entries/11',
        viewUrl: null,
      },
    ],
    'thumbs'
  );

  clickAction(container, 'View');

  expect(open).not.toHaveBeenCalled();
});

it('lends the nav a gear that opens Customize Sources', async () => {
  await mountPage({customizableSources: true});

  const [gear, ...others] = useNavItemActions()('/admin/entries');

  expect(others).toEqual([]);
  expect(gear?.icon).toBe('gear');

  gear!.onClick!(new Event('click'));
  await nextTick();

  expect(modal.isActive).toBe(true);
});

it('offers it only on pages that opt in', async () => {
  // Not every index built on this page offers Customize Sources — the users
  // index doesn't ask for it.
  await mountPage();

  expect(useNavItemActions()('/admin/entries')).toEqual([]);
});

it.each([
  ['a non-admin', {admin: false, allowAdminChanges: true}],
  [
    'anyone where admin changes are off',
    {admin: true, allowAdminChanges: false},
  ],
])(
  'offers it to nobody but admins who can make changes — not %s',
  async (_, as) => {
    Object.assign(craft, as);

    try {
      await mountPage({customizableSources: true});

      expect(useNavItemActions()('/admin/entries')).toEqual([]);
    } finally {
      Object.assign(craft, {admin: true, allowAdminChanges: true});
    }
  }
);

it('takes the gear back when the page goes', async () => {
  await mountPage({customizableSources: true});

  teardown!();
  teardown = undefined;

  expect(useNavItemActions()('/admin/entries')).toEqual([]);
});
