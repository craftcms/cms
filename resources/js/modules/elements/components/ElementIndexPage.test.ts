import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {useNavItemActions} from '@/common/composables/useNavItemActions';

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

vi.mock('@/modules/elements/components/BaseElementIndex.vue', () => stub);
vi.mock('@/modules/elements/components/DataTable.vue', () => stub);
vi.mock('@/modules/elements/components/ElementCards.vue', () => stub);
vi.mock('@/modules/elements/components/ElementIndexToolbar.vue', () => stub);
vi.mock('@/modules/elements/components/ElementThumbs.vue', () => stub);
// Records whether it's open — which is all a gear in the nav can do to it.
const modal = vi.hoisted(() => ({isActive: false}));

vi.mock(
  '@/modules/elements/components/customize-sources/CustomizeSourcesModal.vue',
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

afterEach(() => {
  teardown?.();
  teardown = undefined;
  modal.isActive = false;
});

async function mountPage(props: Record<string, unknown> = {}) {
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
      h(ElementIndexPage, {
        route: {url: () => '/admin/entries'} as never,
        ...props,
      }),
  });

  app.mount(container);
  teardown = () => {
    app.unmount();
    container.remove();
  };
  await nextTick();
}

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
