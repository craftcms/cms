import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import type {IndexSite} from '@/modules/elements/types/sites';

vi.mock('@craftcms/ui', () => ({
  t: (message: string) => message,
  Appearance: {Fill: 'fill'},
}));

// The toolbar's own controls aren't under test here, so they're reduced to
// inert markup — the real ones are Lit elements that never upgrade in happy-dom.
vi.mock('@craftcms/ui/vue/CraftInput.vue', () => ({
  default: {name: 'CraftInput', render: () => h('input')},
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

vi.mock('@/modules/elements/components/IndexViewSettings.vue', () => ({
  default: {name: 'IndexViewSettings', render: () => h('div')},
}));

vi.mock('@/modules/elements/components/FilterHud.vue', () => ({
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

  app = createApp({
    render: () =>
      h(ElementIndexToolbar, {
        search: '',
        status: '',
        mode: 'table',
        sortField: 'title',
        sortDirection: 'asc',
        tableColumns: [],
        conditions: null,
        columnOptions: [],
        sortOptions: [],
        onSiteChange,
        ...props,
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);

  return {onSiteChange};
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
