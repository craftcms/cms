import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import CompareVersionHeader from './CompareVersionHeader.vue';

let app: ReturnType<typeof createApp> | undefined;
let container: HTMLElement | undefined;

beforeEach(() => {
  // `craft-icon`s fetch their SVGs; left in flight they're aborted at
  // teardown and reported as unhandled errors.
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
});

afterEach(() => {
  app?.unmount();
  container?.remove();
  vi.unstubAllGlobals();
});

const menu = (label: string) => [
  {type: 'link' as const, label, href: `/${label}`, selected: true},
];

/** The menus the header shows, by their buttons' labels. */
async function shownMenus(
  shared: {site: boolean; version: boolean},
  menus: {site?: boolean; version?: boolean} = {site: true, version: true}
): Promise<string[]> {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(CompareVersionHeader, {
        side: 'to',
        headingId: 'compare-to-title',
        label: 'Extended run',
        siteName: menus.site ? 'Français' : null,
        siteMenu: menus.site ? menu('Français') : [],
        versionMenu: menus.version ? menu('Extended run') : [],
        shared,
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await nextTick();

  return [...container.querySelectorAll('craft-action-menu')].map((menu) =>
    menu
      .querySelector('craft-button')!
      .textContent!.trim()
      .split(/\s{2,}|Choose/)[0]!
      .trim()
  );
}

it.each([
  [
    'both menus when the sites and versions differ',
    {site: false, version: false},
    ['Français', 'Extended run'],
  ],
  [
    'only the site menu when only the sites differ',
    {site: false, version: true},
    ['Français'],
  ],
  [
    'only the version menu when only the versions differ',
    {site: true, version: false},
    ['Extended run'],
  ],
  [
    'the version menu when neither differs',
    {site: true, version: true},
    ['Extended run'],
  ],
])('shows %s', async (_, shared, expected) => {
  expect(await shownMenus(shared)).toEqual(expected);
});

it('shows the site menu when it’s the only one, even if both sides share the site', async () => {
  expect(
    await shownMenus({site: true, version: true}, {site: true, version: false})
  ).toEqual(['Français']);
});
