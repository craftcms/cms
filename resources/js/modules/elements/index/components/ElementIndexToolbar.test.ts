import {afterEach, expect, it, vi} from 'vite-plus/test';
import {
  createApp,
  h,
  nextTick,
  reactive,
  type App,
  type Component,
  type SetupContext,
} from 'vue';
import type {IndexSite} from '@/modules/elements/types/sites';

vi.mock('@craftcms/ui', () => ({
  t: (message: string) => message,
  Appearance: {Fill: 'fill'},
  ButtonVariant: {Fill: 'fill'},
}));

vi.mock('@craftcms/ui/vue/CraftInput.vue', () => ({
  default: {
    name: 'CraftInput',
    inheritAttrs: false,
    setup(_props: unknown, {attrs, slots}: SetupContext) {
      return () => h('craft-input', attrs, slots.default?.());
    },
  },
}));

vi.mock('@craftcms/ui/vue/CraftSelectRich.vue', () => ({
  default: {
    name: 'CraftSelectRich',
    props: ['options', 'modelValue'],
    render(this: {options?: Array<{label: string; value: string}>}) {
      return h(
        'select-rich',
        {class: 'stub-select'},
        (this.options ?? []).map((option) =>
          h('option', {value: option.value}, option.label)
        )
      );
    },
  },
}));

vi.mock('@/common/form/Select.vue', () => ({
  default: {
    name: 'Select',
    props: ['options', 'modelValue'],
    render(this: {
      options: Array<{label: string; value: string}>;
      modelValue: string;
    }) {
      return h('select-stub', {
        'data-options': this.options.map((option) => option.value).join(','),
        'data-value': this.modelValue,
      });
    },
  },
}));

vi.mock('@/common/form/CheckboxGroup.vue', () => ({
  default: {name: 'CheckboxGroup', render: () => h('checkbox-group-stub')},
}));

vi.mock('@/modules/elements/index/components/FilterHud.vue', () => ({
  default: {name: 'FilterHud', render: () => h('div')},
}));

vi.mock('@/modules/elements/ElementStatus.vue', () => ({
  default: {name: 'ElementStatus', render: () => h('span')},
}));

const {default: ElementIndexToolbar} =
  await import('./ElementIndexToolbar.vue');

let app: App | undefined;
let container: HTMLElement | undefined;

function site(id: number, handle: string, name: string): IndexSite {
  return {id, handle, name, group: 'Group'};
}

function mount(props: Record<string, unknown>) {
  container = document.createElement('div');
  document.body.append(container);

  const onSiteChange = vi.fn();
  const onSearchUpdate = vi.fn();
  const onSortDirectionUpdate = vi.fn();
  const toolbarProps = reactive<Record<string, unknown>>({
    search: '',
    status: '',
    mode: 'table',
    sortField: 'title',
    sortDirection: 'asc',
    tableColumns: [],
    conditions: null,
    columnOptions: [],
    sortOptions: [],
    ...props,
  });
  const state = reactive({
    sortDirection: (toolbarProps.sortDirection ?? 'asc') as 'asc' | 'desc',
  });

  app = createApp({
    render: () =>
      h(ElementIndexToolbar as Component, {
        ...toolbarProps,
        sortDirection: state.sortDirection,
        onSiteChange,
        'onUpdate:search': onSearchUpdate,
        'onUpdate:sortDirection': (value: 'asc' | 'desc') => {
          state.sortDirection = value;
          onSortDirectionUpdate(value);
        },
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);

  return {
    onSiteChange,
    onSearchUpdate,
    onSortDirectionUpdate,
    state,
    toolbarProps,
  };
}

function siteMenu(): HTMLElement | null {
  return container!.querySelector<HTMLElement>('.element-toolbar__site');
}

afterEach(() => {
  app?.unmount();
  container?.remove();
  vi.restoreAllMocks();
});

it('offers no site menu when the index sends no sites', () => {
  mount({});

  expect(siteMenu()).toBeNull();
});

it('offers no site menu for a single site', () => {
  mount({sites: [site(1, 'default', 'Default')]});

  expect(siteMenu()).toBeNull();
});

it('lists every site once there is a choice', () => {
  mount({
    sites: [site(1, 'default', 'Default'), site(2, 'fr', 'French')],
    siteHandle: 'default',
  });

  const options = [...siteMenu()!.querySelectorAll('option')];

  expect(options.map((option) => option.textContent)).toEqual([
    'Default',
    'French',
  ]);
  expect(options.map((option) => option.getAttribute('value'))).toEqual([
    'default',
    'fr',
  ]);
});

it('reports the handle the user picked', () => {
  const {onSiteChange} = mount({
    sites: [site(1, 'default', 'Default'), site(2, 'fr', 'French')],
    siteHandle: 'default',
  });

  const select = siteMenu()!.querySelector('select-rich')!;
  Object.assign(select, {modelValue: 'fr'});
  select.dispatchEvent(
    new CustomEvent('model-value-changed', {
      detail: {isTriggeredByUser: true},
    })
  );

  expect(onSiteChange).toHaveBeenCalledWith('fr');
});

it('ignores a change the user did not make', () => {
  const {onSiteChange} = mount({
    sites: [site(1, 'default', 'Default'), site(2, 'fr', 'French')],
    siteHandle: 'default',
  });

  const select = siteMenu()!.querySelector('select-rich')!;
  Object.assign(select, {modelValue: 'fr'});
  select.dispatchEvent(
    new CustomEvent('model-value-changed', {
      detail: {isTriggeredByUser: false},
    })
  );

  expect(onSiteChange).not.toHaveBeenCalled();
});

it('omits the shared cell when there is no site menu and no statuses', () => {
  mount({});

  // An empty cell would still take its grid gap.
  expect(container!.querySelector('.element-toolbar__status')).toBeNull();
});

it('puts the site menu and the status menu in one cell', () => {
  mount({
    sites: [site(1, 'default', 'Default'), site(2, 'fr', 'French')],
    siteHandle: 'default',
    statusOptions: [{label: 'All', value: ''}],
  });

  const cell = container!.querySelector('.element-toolbar__status')!;

  expect(cell.querySelector('.element-toolbar__site')).not.toBeNull();
  expect(cell.querySelectorAll('select-rich')).toHaveLength(2);
});

it('keeps score available until clearing search restores the sort, and locks its direction', async () => {
  const {toolbarProps} = mount({
    search: 'needle',
    sortField: 'score',
    sortOptions: [
      {label: 'Title', value: 'title', defaultDir: 'asc'},
      {label: 'Date', value: 'dateCreated', defaultDir: 'desc'},
    ],
  });

  const select = container!.querySelector('select-stub')!;
  const descending = container!.querySelector<
    HTMLElement & {disabled: boolean}
  >('craft-button[aria-label="Sort descending"]')!;

  expect(select.getAttribute('data-options')).toBe('score,title,dateCreated');
  expect(descending.disabled).toBe(true);
  toolbarProps.search = '';
  await nextTick();
  expect(select.getAttribute('data-options')).toBe('score,title,dateCreated');
  toolbarProps.sortField = 'title';
  await nextTick();
  expect(select.getAttribute('data-options')).toBe('title,dateCreated');
});

it('removes score without a search and locks custom ordering', () => {
  mount({
    sortField: 'sortOrder',
    sortOptions: [
      {label: 'Score', value: 'score', defaultDir: 'desc'},
      {label: 'Custom', value: 'sortOrder', defaultDir: 'asc'},
      {label: 'Title', value: 'title', defaultDir: 'asc'},
    ],
  });

  const select = container!.querySelector('select-stub')!;
  const descending = container!.querySelector<
    HTMLElement & {disabled: boolean}
  >('craft-button[aria-label="Sort descending"]')!;

  expect(select.getAttribute('data-options')).toBe('sortOrder,title');
  expect(descending.disabled).toBe(true);
});

it('changes sort direction when its actual direction button is clicked', async () => {
  const {onSortDirectionUpdate, state} = mount({
    sortOptions: [{label: 'Title', value: 'title', defaultDir: 'asc'}],
  });
  const ascending = container!.querySelector<HTMLElement & {active: boolean}>(
    'craft-button[aria-label="Sort ascending"]'
  )!;
  const descending = container!.querySelector<HTMLElement & {active: boolean}>(
    'craft-button[aria-label="Sort descending"]'
  )!;

  expect(ascending.active).toBe(true);
  expect(descending.active).toBe(false);
  expect(ascending.getAttribute('aria-pressed')).toBe('true');
  expect(descending.getAttribute('aria-pressed')).toBe('false');

  descending.click();
  await nextTick();

  expect(onSortDirectionUpdate).toHaveBeenCalledWith('desc');
  expect(state.sortDirection).toBe('desc');
  expect(ascending.active).toBe(false);
  expect(descending.active).toBe(true);
  expect(ascending.getAttribute('aria-pressed')).toBe('false');
  expect(descending.getAttribute('aria-pressed')).toBe('true');
});

it('returns focus to the search input when the search is cleared', () => {
  const {onSearchUpdate} = mount({search: 'needle'});
  const input = container!.querySelector<HTMLElement>(
    'craft-input[name="search"]'
  )!;
  const focus = vi.spyOn(input, 'focus');

  container!
    .querySelector('craft-icon[label="Clear search"]')!
    .closest<HTMLElement>('craft-button')!
    .click();

  expect(onSearchUpdate).toHaveBeenCalledWith('');
  expect(focus).toHaveBeenCalled();
});

it('keeps the search input enabled while results load', () => {
  mount({processing: true});

  expect(
    container!
      .querySelector('craft-input[name="search"]')!
      .hasAttribute('disabled')
  ).toBe(false);
});
