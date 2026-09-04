import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';

/**
 * `useElementIndexPage` hands back a `reactive` object, not refs — reading it
 * through `.value` is `undefined`, which throws in setup and leaves the page
 * rendering nothing at all behind the layout chrome. Mirroring that shape here
 * is the point of the test.
 */
const page = vi.hoisted(() => ({
  elementIndex: null as unknown,
  viewState: null as unknown,
}));

vi.mock('@/modules/elements/composables/useElementIndexPage', () => ({
  useElementIndexPage: () => page.elementIndex,
}));
vi.mock('@/modules/elements/composables/useElementQuickEdit', () => ({
  useElementQuickEdit: () => ({}),
}));
vi.mock('@inertiajs/vue3', () => ({
  router: {visit: vi.fn(), prefetch: vi.fn()},
  usePage: () => ({props: {}}),
}));
vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({site: {handle: 'default'}}),
}));

const sourceActions = vi.hoisted(() => ({
  options: null as null | Record<string, (() => unknown) | undefined>,
}));

vi.mock('@/modules/elements/composables/useElementSourceActions', () => ({
  useElementSourceActions: (
    options: Record<string, (() => unknown) | undefined>
  ) => {
    sourceActions.options = options;

    return {actions: {value: []}};
  },
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

vi.mock('@/modules/elements/components/BaseElementIndex.vue', () => stub);
vi.mock('@/modules/elements/components/DataTable.vue', () => stub);
vi.mock('@/modules/elements/components/ElementCards.vue', () => stub);
vi.mock('@/modules/elements/components/ElementIndexToolbar.vue', () => stub);
vi.mock('@/modules/elements/components/ElementThumbs.vue', () => stub);
vi.mock(
  '@/modules/elements/components/customize-sources/CustomizeSourcesModal.vue',
  () => stub
);

const ElementIndexPage = (await import('./ElementIndexPage.vue')).default;

let teardown: (() => void) | undefined;

afterEach(() => {
  teardown?.();
  teardown = undefined;
});

it('hands the secondary nav its live sources', async () => {
  const viewState = reactive({mode: 'table'});

  page.elementIndex = {
    elementIndex: reactive({
      sources: [
        {type: 'native', key: '*', label: 'All entries'},
        {type: 'native', key: 'section:blog', label: 'Blog'},
      ],
      source: {key: '*'},
      pagination: {from: 1, to: 2, total: 2},
      actions: [],
      elementType: 'entry',
      context: 'index',
      viewModes: [],
      statusOptions: [],
    }),
    elementTable: {},
    viewState,
    conditions: {},
    filters: {},
    columnOptions: [],
    tableColumns: [],
    reorder: vi.fn(),
    sortField: null,
    sortDirection: null,
    mode: 'table',
    loading: false,
    visibleViewModes: [],
    onActionPerformed: vi.fn(),
  };

  const container = document.createElement('div');
  document.body.append(container);

  const app = createApp({
    render: () =>
      h(ElementIndexPage, {route: {url: () => '/admin/entries'} as never}),
  });

  app.mount(container);
  teardown = () => {
    app.unmount();
    container.remove();
  };
  await nextTick();

  // `useElementIndexPage` returns a reactive object rather than refs, so
  // reaching through `.value` yields undefined and throws in setup — which
  // renders the whole page as nothing behind the layout chrome.
  const options = sourceActions.options!;

  expect(options).not.toBeNull();
  expect(options.sources!()).toEqual([
    {type: 'native', key: '*', label: 'All entries'},
    {type: 'native', key: 'section:blog', label: 'Blog'},
  ]);
  expect(options.activeSource!()).toBe('*');
});
