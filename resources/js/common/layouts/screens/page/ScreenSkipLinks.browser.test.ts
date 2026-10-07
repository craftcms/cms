import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {createApp, defineComponent, h, nextTick, reactive, ref} from 'vue';
import type {App} from 'vue';
import {expectUnobscured} from '@/test/expect-unobscured';
import '../../../../../css/cp.css';

/**
 * Global skip links must paint above the persistent CP chrome when focused.
 * `toBeVisible()` passes for covered elements, so this hit-tests a real layout
 * instead.
 * See "Skip link / bypass blocks" in .github/instructions/a11y.instructions.md.
 */

const state = vi.hoisted(() => ({page: null as any}));
const isLarge = ref(true);

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => state.page,
}));

vi.mock('@/common/composables/useCpBreakpoints', () => ({
  cpBreakpoints: {greaterOrEqual: () => isLarge},
}));

vi.mock('@/common/composables/useGlobalSidebar', () => ({
  useGlobalSidebar: () => ({
    toggle: vi.fn(),
    toggleButton: ref<HTMLElement | null>(null),
  }),
}));

let app: App | null = null;
let container: HTMLElement | null = null;

afterEach(() => {
  app?.unmount();
  app = null;
  container?.remove();
  container = null;
});

/**
 * Mounts the skip links and the header bar the way `PageScreen` does: the
 * links first, then the header bar inside `.page-screen`.
 */
async function mountScreenTop(large: boolean): Promise<HTMLElement> {
  isLarge.value = large;
  state.page = reactive({
    url: '/admin',
    props: {
      craft: {
        csrfTokenValue: 'token',
        csrfTokenName: 'CRAFT_CSRF_TOKEN',
        system: {name: 'Craft CMS', icon: null},
        app: {
          version: '6.0.0',
          edition: {name: 'Pro', handle: 'pro', value: 2},
        },
        site: null,
        readOnly: false,
        maintenanceMode: false,
        devMode: false,
        allowAdminChanges: true,
        currentUser: {
          username: 'admin',
          email: 'admin@example.com',
          id: 1,
          thumbHtml: null,
          name: 'Admin',
        },
        general: {
          cpTrigger: 'admin',
          actionTrigger: 'actions',
          csrfTokenName: 'CRAFT_CSRF_TOKEN',
          cpLogoUrl: null,
          useEmailAsUsername: false,
          rememberedUserSessionDuration: 0,
          defaultCpLocale: 'en-US',
          notifications: [],
        },
        nav: [],
        navBadges: {},
        orientation: 'ltr',
        actionUrl: 'https://example.test/actions',
        cpUrl: 'https://example.test/admin',
        baseApiUrl: 'https://example.test/api',
      },
    },
  });

  const {default: ScreenSkipLinks} = await import('./ScreenSkipLinks.vue');
  const {default: CpHeaderBar} =
    await import('@/common/components/CpHeaderBar.vue');

  container = document.createElement('div');
  document.body.append(container);
  app = createApp(
    defineComponent({
      render: () => [
        h(ScreenSkipLinks, {hasSidebar: true}),
        h('div', {class: 'page-screen'}, [
          h(CpHeaderBar, {crumbs: [], hasContextMenu: false}),
          h('main', {id: 'main', tabindex: -1}, 'Main'),
          h('nav', {id: 'secondary-nav'}, 'Secondary'),
        ]),
      ],
    })
  );
  app.mount(container);
  await nextTick();

  return container;
}

describe.each([
  {label: 'desktop', large: true, width: 1280},
  {label: 'mobile', large: false, width: 375},
])('global skip links on $label', ({large, width}) => {
  it('paint above the header bar when focused', async () => {
    await page.viewport(width, 800);
    const root = await mountScreenTop(large);
    const links = [
      ...root.querySelectorAll<HTMLAnchorElement>(
        '.skip-link--global:not([data-messages-skip-link])'
      ),
    ];

    expect(links.length).toBeGreaterThan(0);

    (document.activeElement as HTMLElement | null)?.blur();
    for (const link of links) {
      await userEvent.tab();
      expect(document.activeElement).toBe(link);
      expectUnobscured(link);
    }
  });
});
