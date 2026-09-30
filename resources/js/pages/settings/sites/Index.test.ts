import {FlexRender} from '@tanstack/vue-table';
import {computed, createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {Site} from '@/common/types';
import Index from './Index.vue';

vi.mock('@actions/Settings/SitesController', () => ({
  create: () => ({url: '/settings/sites/new'}),
  edit: {url: ({site}: {site: number}) => `/settings/sites/${site}`},
  reorder: () => ({url: '/settings/sites/reorder'}),
}));

vi.mock('@actions/Settings/SiteGroupsController.js', () => ({
  destroy: () => ({url: '/settings/sites/groups'}),
  store: () => ({url: '/settings/sites/groups'}),
}));

vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({readOnly: computed(() => false)}),
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: () => {},
}));

vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({render: () => h('div')}),
}));

vi.mock('@/modules/admin-table/components/AdminTable.vue', () => ({
  default: defineComponent({
    props: ['table'],
    render() {
      return this.table.getRowModel().rows.map((row: any) =>
        row
          .getVisibleCells()
          .filter((cell: any) => cell.column.id === 'primary')
          .map((cell: any) =>
            h('td', {'data-site-id': row.original.id}, [
              h(FlexRender, {
                render: cell.column.columnDef.cell,
                props: cell.getContext(),
              }),
            ])
          )
      );
    },
  }),
}));

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('gives the primary site checkmark an accessible name', async () => {
  await mount();

  const icons = primaryCell(1).querySelectorAll('craft-icon');

  expect(icons).toHaveLength(1);
  expect(icons[0]?.getAttribute('name')).toBe('check');
  expect(icons[0]?.getAttribute('role')).toBe('img');
  expect(icons[0]?.getAttribute('aria-label')).toBe('Yes');
});

it('leaves the primary cell empty for non-primary sites', async () => {
  await mount();

  expect(primaryCell(2).querySelector('craft-icon')).toBeNull();
});

async function mount(): Promise<void> {
  app = createApp(Index, {
    title: 'Sites',
    group: null,
    groups: [],
    sites: [
      site({id: 1, handle: 'primary', primary: true}),
      site({id: 2, handle: 'secondary', primary: false}),
    ],
    flash: {success: null, error: null},
  });
  app.mount(container);
  await nextTick();
}

function primaryCell(siteId: number): HTMLElement {
  const cell = container.querySelector<HTMLElement>(
    `[data-site-id="${siteId}"]`
  );

  expect(cell).not.toBeNull();

  return cell!;
}

function site(overrides: Partial<Site>): Site {
  return {
    id: 1,
    uid: 'site-uid',
    name: 'Test site',
    nameRaw: 'Test site',
    handle: 'testSite',
    language: 'en-US',
    languageRaw: 'en-US',
    enabled: true,
    enabledRaw: true,
    groupId: 1,
    group: null,
    primary: false,
    hasUrls: true,
    baseUrl: '',
    baseUrlRaw: '',
    sortOrder: 1,
    dateCreated: '2026-01-01T00:00:00Z',
    dateUpdated: '2026-01-01T00:00:00Z',
    ...overrides,
  };
}
