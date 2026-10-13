import {computed, createApp, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import LayoutSlot from '@/common/components/LayoutSlot.vue';
import PageScreen from './PageScreen.vue';

type NavItem = CraftCms.Cms.Cp.Data.NavItem;

const {Stub} = await vi.hoisted(async () => {
  const {h} = await import('vue');

  return {Stub: {render: () => h('div')}};
});

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  Head: Stub,
  usePage: () => ({
    url: '/admin/commerce/store-management',
    props: {title: 'General', subnav: [navItem({label: 'General'})]},
  }),
}));
vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({nav: computed(() => []), siteCrumb: computed(() => null)}),
}));
vi.mock('@/common/components/CpSidebar.vue', () => ({default: Stub}));
vi.mock('@/common/components/CpHeaderBar.vue', () => ({default: Stub}));
vi.mock('./page/ScreenOverlays.vue', () => ({default: Stub}));

const realMatchMedia = window.matchMedia;

function stubViewport(isLarge: boolean): void {
  window.matchMedia = ((media: string) => ({
    media,
    matches: isLarge,
    onchange: null,
    addEventListener: () => {},
    removeEventListener: () => {},
    addListener: () => {},
    removeListener: () => {},
    dispatchEvent: () => false,
  })) as unknown as typeof window.matchMedia;
}

function navItem(config: Partial<NavItem> & {label: string}): NavItem {
  return {
    href: `/admin/${config.label.toLowerCase().replace(/\s+/g, '-')}`,
    selected: false,
    subnav: false,
    group: false,
    external: false,
    badgeCount: 0,
    ariaLabel: null,
    icon: null,
    id: null,
    fontIcon: null,
    linkAttributes: {},
    ...config,
  } as NavItem;
}

let teardown: (() => void) | undefined;

beforeEach(() => {
  vi.stubGlobal('Craft', {systemUid: 'page-screen-test', cpTrigger: 'admin'});
});

afterEach(() => {
  vi.unstubAllGlobals();
  teardown?.();
  teardown = undefined;
  window.matchMedia = realMatchMedia;
});

describe('PageScreen', () => {
  it('draws the subnav actions menu only once a page fills it', async () => {
    stubViewport(true);

    const filled = ref(false);
    const container = document.createElement('div');
    document.body.append(container);

    const app = createApp({
      render: () =>
        h(PageScreen, null, {
          default: () =>
            filled.value
              ? h(LayoutSlot, {name: 'subnav-actions'}, () =>
                  h('button', {type: 'button'}, 'New Store')
                )
              : null,
        }),
    });
    app.mount(container);
    teardown = () => {
      app.unmount();
      container.remove();
    };
    await nextTick();

    expect(container.querySelector('.secondary-nav')).not.toBeNull();
    expect(container.querySelector('.secondary-nav__actions')).toBeNull();

    filled.value = true;
    await nextTick();
    await nextTick();
    await nextTick();

    const actions = container.querySelector('.secondary-nav__actions');

    expect(actions).not.toBeNull();
    expect(actions?.textContent).toContain('New Store');
  });
});
