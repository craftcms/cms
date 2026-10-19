import '@craftcms/ui';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import $ from 'jquery';
import {Tab} from './tab';

// Sidebar tabs' default icons would otherwise fetch their SVGs, after the
// tests that render them have finished.
vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));

afterEach(() => {
  delete window.Craft;
  delete window.$;
  document.body.innerHTML = '';
});

it('makes the tab action button flush with the tab’s inline end', async () => {
  window.Craft = Object.assign(Object.create(null), {
    t: (_category: string, message: string) => message,
  });
  const tab = Object.create(Tab.prototype) as Tab;
  tab.$container = document.createElement('div');
  tab.$container.innerHTML = '<div class="tabs"><div class="tab"></div></div>';
  (tab as any).designer = {settings: {readOnly: false}};
  document.body.append(tab.$container);

  tab.createMenu();
  const menu = tab.$container.querySelector('craft-action-menu')!;
  await menu.updateComplete;

  expect(
    menu
      .querySelector(':scope > craft-button[slot="invoker"]')
      ?.getAttribute('flush')
  ).toBe('inline-end');
});

function createTabs(types: Array<'content' | 'sidebar'>): Tab[] {
  window.Craft = Object.assign(Object.create(null), {
    t: (_category: string, message: string) => message,
    orientation: 'ltr',
  });
  // Craft.Grid's jQuery seam; the grid itself is stubbed out.
  window.$ = $;
  const $tabContainer = document.createElement('div');
  $tabContainer.innerHTML =
    '<div class="fld-tabs__content"></div><div class="fld-tabs__sidebar"></div>';
  document.body.append($tabContainer);
  const designer: any = {
    settings: {readOnly: false},
    $tabContainer,
    $contentTabContainer: $tabContainer.firstElementChild,
    $sidebarTabContainer: $tabContainer.lastElementChild,
    tabGrid: {refreshCols() {}, addItems() {}, removeItems() {}},
    tabElements: () =>
      Array.from(
        $tabContainer.querySelectorAll(
          ':scope > .fld-tabs__content > .fld-tab, :scope > .fld-tabs__sidebar > .fld-tab'
        )
      ),
    config: {
      tabs: types.map((type, i) => ({uid: `tab${i}`, name: `Tab ${i}`, type})),
    },
    updateConfig(callback: (config: any) => any) {
      const config = callback(this.config);
      if (config !== false) {
        this.config = config;
      }
    },
  };

  return types.map((type, i) => {
    const tab = Object.create(Tab.prototype) as Tab;
    tab.designer = designer;
    tab.uid = `tab${i}`;
    tab.$container = document.createElement('div');
    tab.$container.className =
      type === 'sidebar' ? 'fld-tab fld-tab--sidebar' : 'fld-tab';
    tab.$container.innerHTML = `<div class="tabs"><div class="tab"><h3 class="fld-tab__name">Tab ${i}</h3></div></div>`;
    (type === 'sidebar'
      ? designer.$sidebarTabContainer
      : designer.$contentTabContainer
    ).append(tab.$container);
    return tab;
  });
}

it('only offers to move tabs among tabs of the same type', () => {
  const [contentTab, sidebarTab] = createTabs(['content', 'sidebar']);

  contentTab!.createMenu();
  sidebarTab!.createMenu();

  const labels = (tab: Tab) =>
    tab.$container
      .querySelector('craft-action-menu')
      .actions()
      .map((item: any) => item.label)
      .filter(Boolean);

  expect(labels(contentTab!)).not.toContain('Move to the right');
  expect(labels(contentTab!)).toContain('Move to the sidebar');
  expect(labels(sidebarTab!)).not.toContain('Move to the left');
  expect(labels(sidebarTab!)).toContain('Move to content');
});

it('moves tabs to the end of their new type', () => {
  const [first, second, sidebar] = createTabs([
    'content',
    'content',
    'sidebar',
  ]);
  const designer = first!.designer as any;
  const order = () =>
    designer.config.tabs.map((tab: any) => `${tab.uid}:${tab.type}`);

  first!.setType('sidebar');

  expect(order()).toEqual(['tab1:content', 'tab2:sidebar', 'tab0:sidebar']);
  expect(first!.$container.classList.contains('fld-tab--sidebar')).toBe(true);
  expect(designer.$sidebarTabContainer.lastElementChild).toBe(
    first!.$container
  );
  expect(first!.$container.querySelector('craft-tooltip')?.textContent).toBe(
    'Tab 0'
  );

  sidebar!.setType('content');

  expect(order()).toEqual(['tab1:content', 'tab2:content', 'tab0:sidebar']);
  expect(designer.$contentTabContainer.lastElementChild).toBe(
    sidebar!.$container
  );
  expect(sidebar!.$container.querySelector('craft-tooltip')).toBeNull();
  expect(second!.$container.nextElementSibling).toBe(sidebar!.$container);
});

it('takes on the type of the column it was dragged into', () => {
  const [content, sidebar] = createTabs(['content', 'sidebar']);
  const designer = content!.designer as any;

  designer.$sidebarTabContainer.append(content!.$container);
  content!.syncTypeWithColumn();
  designer.$contentTabContainer.append(sidebar!.$container);
  sidebar!.syncTypeWithColumn();

  expect(
    designer.config.tabs.map((tab: any) => `${tab.uid}:${tab.type}`)
  ).toEqual(['tab0:sidebar', 'tab1:content']);
  expect(content!.isSidebar).toBe(true);
  expect(content!.$container.querySelector('craft-tooltip')).not.toBeNull();
  expect(sidebar!.isSidebar).toBe(false);
  expect(sidebar!.$container.querySelector('craft-tooltip')).toBeNull();
});
