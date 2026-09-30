import {createApp, h, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {setUrlDefaults} from '@/wayfinder';
import type {ElementIndexRoute} from '@/modules/elements/index/composables/useElementIndexVisits';

const state = vi.hoisted(() => ({route: null as unknown as ElementIndexRoute}));

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    props: {
      source: {key: '*'},
      slug: null,
      sources: [
        {type: 'native', key: '*', data: {slug: 'all'}},
        {type: 'native', key: 'admins', data: {slug: 'admins'}},
      ],
    },
  }),
}));
vi.mock('@/modules/elements/index/components/ElementIndexPage.vue', () => ({
  default: {
    props: ['route'],
    setup: (props: {route: ElementIndexRoute}) => {
      state.route = props.route;
      return () => null;
    },
  },
}));
vi.mock('@/common/components/CpButtonLink.vue', () => ({
  default: {render: () => null},
}));

const {default: UsersIndex} = await import('./Index.vue');
let app: App;

afterEach(() => {
  app?.unmount();
  setUrlDefaults({});
});

it('keeps the bare users path while filtering and changes it when switching sources', () => {
  setUrlDefaults({cpTrigger: 'cp'});
  app = createApp({render: () => h(UsersIndex)});
  app.mount(document.createElement('div'));

  const filtering = new URL(
    state.route.url({source: '*', search: 'needle'}),
    window.location.origin
  );
  const switching = new URL(
    state.route.url({source: 'admins'}),
    window.location.origin
  );

  expect(filtering.pathname).toBe('/cp/users');
  expect(filtering.searchParams.get('search')).toBe('needle');
  expect(switching.pathname).toBe('/cp/users/admins');
});
