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
  teardown?.();
  teardown = undefined;
  vi.unstubAllGlobals();
  window.matchMedia = realMatchMedia;
});

describe('PageScreen', () => {
  it.each([
    {
      layout: 'expanded',
      isLarge: true,
      nav: '.secondary-nav',
      menu: '.secondary-nav__actions',
    },
    {
      layout: 'collapsed',
      isLarge: false,
      nav: 'craft-popover',
      menu: 'craft-action-menu',
    },
  ])(
    'draws the $layout subnav actions menu only once a page fills it',
    async ({isLarge, nav, menu}) => {
      stubViewport(isLarge);

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

      expect(container.querySelector(nav)).not.toBeNull();
      expect(container.querySelector(menu)).toBeNull();

      filled.value = true;

      await vi.waitFor(() =>
        expect(container.querySelector(menu)?.textContent ?? '').toContain(
          'New Store'
        )
      );
    }
  );

  it('hides the content header while it has nothing to show', async () => {
    stubViewport(true);

    const withActions = ref(false);
    const container = document.createElement('div');
    document.body.append(container);

    const app = createApp({
      render: () =>
        h(PageScreen, {title: ''} as never, {
          default: () =>
            withActions.value
              ? h(LayoutSlot, {name: 'content-actions'}, () =>
                  h('button', {type: 'button'}, 'Action')
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

    const header = () =>
      container.querySelector<HTMLElement>('#cp-content-header')!;
    expect(header().style.display).toBe('none');

    withActions.value = true;

    await vi.waitFor(() => expect(header().style.display).toBe(''));
  });

  it('hides the footer for a page that asks it to, even with a form', async () => {
    stubViewport(true);

    const hideFooter = ref(false);
    const form = {
      hasErrors: false,
      errors: {},
      processing: false,
      isDirty: false,
    } as unknown as InstanceType<typeof Object>;
    const container = document.createElement('div');
    document.body.append(container);

    const app = createApp({
      render: () =>
        h(PageScreen, {
          form,
          defaultFormActions: [],
          hideFooter: hideFooter.value,
        } as never),
    });
    app.mount(container);
    teardown = () => {
      app.unmount();
      container.remove();
    };
    await nextTick();

    const footer = () =>
      container.querySelector<HTMLElement>('.content-footer');
    expect(
      footer()?.closest<HTMLElement>('[style*="display: none"]')
    ).toBeNull();

    hideFooter.value = true;
    await nextTick();

    expect(
      footer()?.closest<HTMLElement>('[style*="display: none"]')
    ).not.toBeNull();
  });
});
