import '../../../../css/cp.css';
import '@craftcms/ui/components/radio/radio';
import '@craftcms/ui/components/radio-group/radio-group';
import '@craftcms/ui/components/select/select';
import '@craftcms/ui/components/pane/pane';
import {router} from '@inertiajs/core';
import {createApp, defineComponent, reactive, watchEffect} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import type {UseAppLayoutOptions} from '@/common/composables/useAppLayout';
import type {TableProps} from '@/modules/ui/table-types';
import Index from './Index.vue';

let layout: UseAppLayoutOptions;
let inertiaPage: {url: string; props: Record<string, unknown>};
let teardown: () => void;

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => inertiaPage,
    Deferred: defineComponent({
      props: ['data'],
      setup:
        (_, {slots}) =>
        () =>
          slots.default?.(),
    }),
  };
});
vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: (getOptions: () => UseAppLayoutOptions) => {
    watchEffect(() => (layout = getOptions()));
  },
}));
vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({
    props: ['name'],
    setup:
      (_, {slots}) =>
      () =>
        slots.default?.(),
  }),
}));

beforeEach(() => {
  localStorage.clear();
  vi.stubGlobal('Craft', {systemUid: 'sites-test', cpTrigger: 'admin'});
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
  inertiaPage = reactive({
    url: '/admin/settings/sites?groupId=7',
    props: {
      readOnly: false,
      craft: {readOnly: false},
      nameTextExpanderTriggers: [],
      transferContentOptions: [
        {id: 1, name: 'Primary'},
        {id: 2, name: 'Secondary'},
        {id: 3, name: 'Another group'},
      ],
    },
  });
});
afterEach(() => {
  teardown?.();
  document.body.innerHTML = '';
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

function mount(readOnly = false) {
  inertiaPage.props.readOnly = readOnly;
  inertiaPage.props.craft = {readOnly};
  const table: TableProps = {
    columns: [
      {key: 'name', label: 'Name'},
      {key: 'primary', label: 'Primary'},
    ],
    rows: [
      {
        id: 1,
        name: {label: 'Primary', url: '/admin/settings/sites/1'},
        primary: {icon: 'check', label: 'Yes'},
        _deletable: false,
        _deleteDisabledReason: 'You cannot delete the primary site.',
        _deleteUrl: '/admin/settings/sites/1',
      },
      {
        id: 2,
        name: {label: 'Secondary', url: '/admin/settings/sites/2'},
        primary: null,
        _deleteUrl: '/admin/settings/sites/2',
      },
    ],
    dataUrl: null,
    perPage: 100,
    perPageOptions: [50, 100, 250],
    moveToPageUrl: null,
    emptyMessage: 'No sites exist yet.',
    createLabel: 'New Site',
    createUrl: readOnly ? null : '/admin/settings/sites/new?groupId=7',
    createMenuItems: null,
    createActionInPageHeader: true,
    reorderUrl: null,
    reorderSuccessMessage: null,
    reorderFailMessage: null,
    deleteUrl: null,
    deletable: !readOnly,
    deleteConfirmMessage: null,
    deleteModalUrl: null,
    bulkDeleteUrl: null,
    bulkDeleteConfirmMessage: null,
    bulkActions: [],
    statusActions: [],
    statusFilterOptions: [],
    columnsToggleable: false,
    hiddenColumnsByDefault: [],
    searchable: false,
    searchPlaceholder: null,
    bordered: false,
    showFooter: false,
  };
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp(Index, {
    title: 'Group',
    group: {id: 7, uid: 'group-uid', rawName: '$GROUP_NAME', name: 'Group'},
    ui: {
      nodes: [
        {
          uid: 'sites',
          component: 'craft:admin-table',
          type: 'table',
          props: table,
        },
      ],
      values: {},
      errors: [],
      scope: [],
      refreshable: false,
    },
    flash: {success: null, error: null},
    nameTextExpanderTriggers: [],
  });
  app.mount(host);
  teardown = () => app.unmount();
  return host;
}

it('names the primary mark and explains why its deletion is disabled', async () => {
  const host = mount();
  await expect
    .element(page.getByRole('img', {name: 'Yes', exact: true}))
    .toBeVisible();
  const primary = host.querySelectorAll('tbody tr')[0]!;
  const remove = primary.querySelector<HTMLElement>('craft-button')!;
  await vi.waitFor(() =>
    expect(remove.getAttribute('aria-disabled')).toBe('true')
  );
  await expect
    .element(page.elementLocator(remove))
    .toHaveAccessibleDescription('You cannot delete the primary site.');
  await (remove as HTMLElementTagNameMap['craft-button']).updateComplete;
  remove.focus();
  expect(document.activeElement).toBe(remove);
  await userEvent.keyboard('{Enter}');
  expect(host.querySelector('craft-dialog[open]')).toBeNull();
});

it('opens the existing transfer dialog for the selected row and resets it after canceling', async () => {
  const host = mount();
  const remove = host.querySelectorAll<HTMLElement>('tbody craft-button')[1]!;
  await (remove as HTMLElementTagNameMap['craft-button']).updateComplete;
  remove.focus();
  expect(document.activeElement).toBe(remove);
  await userEvent.keyboard('{Enter}');
  await expect
    .element(page.getByRole('heading', {name: 'Delete Secondary', exact: true}))
    .toBeVisible();
  await expect
    .element(page.getByRole('dialog', {name: 'Delete Secondary'}))
    .toBeVisible();
  const select = host.querySelector<HTMLSelectElement>('select')!;
  expect(
    Array.from(select.options, (option) => option.textContent?.trim())
  ).toEqual(['Select site', 'Primary', 'Another group']);
  await page.getByRole('radio', {name: 'Delete it', exact: true}).click();
  await vi.waitFor(() => expect(host.querySelector('select')).toBeNull());
  await userEvent.keyboard('{Escape}');
  await vi.waitFor(() =>
    expect(host.querySelector('craft-dialog[open]')).toBeNull()
  );
  expect(document.activeElement).toBe(remove);
  expect(host.querySelectorAll('tbody tr')).toHaveLength(2);
  await (remove as HTMLElementTagNameMap['craft-button']).updateComplete;
  remove.focus();
  expect(document.activeElement).toBe(remove);
  await userEvent.keyboard('{Enter}');
  await vi.waitFor(() => expect(host.querySelector('select')).not.toBeNull());
  const submit = vi.spyOn(router, 'delete').mockImplementation(() => {});
  await page
    .getByRole('combobox', {name: 'Transfer content to'})
    .selectOptions('3');
  await page.getByRole('button', {name: 'Delete', exact: true}).click();
  await vi.waitFor(() =>
    expect(submit).toHaveBeenCalledWith(
      '/admin/settings/sites/2',
      expect.objectContaining({
        data: {id: 2, contentDestination: 'transfer', transferContentTo: '3'},
      })
    )
  );
});

it('retains group rename and creation dialogs while hiding mutations in read-only mode', async () => {
  const host = mount();
  const menu = host.querySelector('craft-action-menu')!;
  await page.getByRole('button', {name: 'Site group Actions'}).click();
  await page
    .elementLocator(menu)
    .getByText('Rename Group', {exact: true})
    .click();
  await expect
    .element(page.getByRole('dialog', {name: 'Rename Group'}))
    .toBeVisible();
  await expect
    .element(page.getByRole('textbox', {name: 'Group Name'}))
    .toHaveValue('$GROUP_NAME');
  const save = vi.spyOn(router, 'post').mockImplementation(() => {});
  await page.getByRole('button', {name: 'Save', exact: true}).click();
  await vi.waitFor(() =>
    expect(save).toHaveBeenCalledWith(
      '/admin/settings/site-groups',
      {id: 7, name: '$GROUP_NAME'},
      expect.any(Object)
    )
  );
  await userEvent.keyboard('{Escape}');
  await vi.waitFor(() =>
    expect(host.querySelector('craft-dialog[open]')).toBeNull()
  );
  await vi.waitFor(() =>
    expect(document.activeElement).toBe(menu.querySelector('[slot="invoker"]'))
  );
  const create = layout.subnavActions?.[0];
  if (!create || !('onClick' in create))
    throw new Error('Missing group creation action');
  create.onClick?.(new MouseEvent('click'));
  await expect
    .element(page.getByRole('textbox', {name: 'Group Name'}))
    .toHaveValue('');
  teardown();
  host.remove();
  const readOnlyHost = mount(true);
  expect(layout.subnavActions).toEqual([]);
  expect(readOnlyHost.querySelector('craft-action-menu')).toBeNull();
});
