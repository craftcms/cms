import {describe, expect, it} from 'vite-plus/test';
import {
  subnavCrumbs,
  withNavCrumbMenus,
  withSubnavCrumbs,
} from './subnavCrumbs';

import type {BreadcrumbItem} from '@/common/types';

type NavItem = CraftCms.Cms.Cp.Data.NavItem;

function navCrumbItem(config: Partial<NavItem> & {label: string}): NavItem {
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

const SETTINGS = [
  navCrumbItem({
    label: 'Volumes',
    href: '/admin/settings/assets',
    selected: true,
  }),
  navCrumbItem({
    label: 'Image Transforms',
    href: '/admin/settings/assets/transforms',
  }),
  navCrumbItem({
    label: 'Asset Transformers',
    href: '/admin/settings/assets/transformers',
  }),
];

describe('subnavCrumbs', () => {
  it('names the selected item and offers its siblings', () => {
    const [crumb, ...rest] = subnavCrumbs(SETTINGS);

    expect(rest).toEqual([]);
    expect(crumb!.label).toBe('Volumes');
    expect(crumb!.href).toBe('/admin/settings/assets');
    expect(crumb!.items?.map((action) => (action as any).label)).toEqual([
      'Volumes',
      'Image Transforms',
      'Asset Transformers',
    ]);
    // The one you're on is marked, so the menu reads as a choice.
    expect(crumb!.items?.map((action) => (action as any).selected)).toEqual([
      true,
      false,
      false,
    ]);
  });

  it('carries an item icon into the switcher', () => {
    const [crumb] = subnavCrumbs([
      navCrumbItem({
        label: 'Deprecation Warnings',
        icon: 'bug',
        selected: true,
      }),
      navCrumbItem({label: 'Queue Manager', icon: 'list-check'}),
    ]);

    // The secondary nav's own menu shows these, so the switcher has to as well
    // — they're the same menu in two places.
    expect(crumb!.items?.map((action) => (action as any).icon)).toEqual([
      'bug',
      'list-check',
    ]);
  });

  it('leaves a lone item without a menu', () => {
    const [crumb] = subnavCrumbs([
      navCrumbItem({label: 'Volumes', selected: true}),
    ]);

    // One option is no choice at all.
    expect(crumb!.items).toBeUndefined();
  });

  it('walks into a group, naming each level', () => {
    const crumbs = subnavCrumbs([
      navCrumbItem({label: 'Profile'}),
      navCrumbItem({
        label: 'Account Security',
        href: '#',
        group: true,
        selected: true,
        subnav: [
          navCrumbItem({label: 'Password', selected: true}),
          navCrumbItem({label: 'Sign-in Providers'}),
        ],
      }),
    ]);

    expect(crumbs.map((crumb) => crumb.label)).toEqual([
      'Account Security',
      'Password',
    ]);
    // A group heads its children rather than being somewhere to go, so the
    // crumb links nowhere — but it survives in the menu as the heading it is
    // in the nav, so the switcher reads the same as the list beside it.
    expect(crumbs[0]!.href).toBeNull();
    expect(
      crumbs[0]!.items?.map((action) =>
        action.type === 'group'
          ? [action.heading, action.items.map((item) => item.label)]
          : (action as any).label
      )
    ).toEqual([
      'Profile',
      ['Account Security', ['Password', 'Sign-in Providers']],
    ]);
    expect(crumbs[1]!.items?.map((action) => (action as any).label)).toEqual([
      'Password',
      'Sign-in Providers',
    ]);
  });

  it('says nothing when nothing is selected', () => {
    expect(subnavCrumbs([navCrumbItem({label: 'Volumes'})])).toEqual([]);
    expect(subnavCrumbs([])).toEqual([]);
  });
});

describe('withSubnavCrumbs', () => {
  it('takes the place of a crumb the page already wrote', () => {
    const merged = withSubnavCrumbs(
      [
        {label: 'Settings', href: '/admin/settings'},
        {label: 'Assets', href: '/admin/settings/assets'},
        {label: 'Volumes'},
      ],
      SETTINGS
    );

    // An index screen's last crumb is the selected nav item by another name.
    expect(merged.map((crumb) => crumb.label)).toEqual([
      'Settings',
      'Assets',
      'Volumes',
    ]);
    expect(merged.at(-1)!.items).toHaveLength(3);
  });

  it('adds a level the page never had', () => {
    const merged = withSubnavCrumbs(
      [
        {label: 'Users', href: '/admin/users'},
        {html: '<craft-chip></craft-chip>'},
      ],
      [
        navCrumbItem({label: 'Profile'}),
        navCrumbItem({label: 'Permissions', selected: true}),
      ]
    );

    // The account screens end in a chip for the user, so the screen you're on
    // was never named at all.
    expect(merged.map((crumb) => crumb.label ?? '(chip)')).toEqual([
      'Users',
      '(chip)',
      'Permissions',
    ]);
  });

  it('leaves the page crumbs alone when there is no nav', () => {
    const crumbs = [{label: 'Settings', href: '/admin/settings'}];

    expect(withSubnavCrumbs(crumbs, [])).toEqual(crumbs);
  });
});

describe('withNavCrumbMenus', () => {
  const ENTRIES = navCrumbItem({
    label: 'Entries',
    href: '/admin/content/entries',
    subnav: [
      navCrumbItem({label: 'All entries', href: '/admin/content/entries'}),
      navCrumbItem({label: 'Singles', href: '/admin/content/entries/singles'}),
      navCrumbItem({
        label: 'Channels',
        href: null,
        group: true,
        subnav: [
          navCrumbItem({label: 'Posts', href: '/admin/content/entries/posts'}),
        ],
      }),
    ],
  });
  const NAV = [navCrumbItem({label: 'Dashboard'}), ENTRIES];

  it('swaps a switcher for the menu the nav draws at its level', () => {
    const [entries, posts] = withNavCrumbMenus(
      [
        {label: 'Entries', href: '/admin/content/entries'},
        {
          label: 'Posts',
          href: '/admin/content/entries/posts',
          items: [{type: 'link', label: 'Singles', href: '/admin/singles'}],
        },
      ],
      NAV
    );

    expect(entries!.items).toBeUndefined();
    expect(posts!.items).toMatchObject([
      {type: 'link', label: 'All entries', selected: false},
      {type: 'link', label: 'Singles', selected: false},
      {
        type: 'group',
        heading: 'Channels',
        items: [{type: 'link', label: 'Posts', selected: true}],
      },
    ]);
  });

  it('keeps the server menu for a crumb the nav doesn’t know', () => {
    const items = [{type: 'link' as const, label: 'Other', href: '/x'}];
    const [crumb] = withNavCrumbMenus(
      [{label: 'Elsewhere', href: '/admin/elsewhere', items}],
      NAV
    );

    expect(crumb!.items).toBe(items);
  });

  it('gives an index’s own crumb its sources, not the main nav', () => {
    // The "all entries" source is linked by the bare index URL, which is also
    // the Entries nav item's own href. Matching the outer level first handed
    // this crumb Dashboard/Entries/Assets instead of the sources beneath it.
    const [crumb] = withNavCrumbMenus(
      [
        {
          label: 'All entries',
          href: '/admin/content/entries',
          items: [{type: 'link', label: 'All entries', href: '/x'}],
        },
      ],
      NAV
    );

    expect(crumb!.items).toMatchObject([
      {type: 'link', label: 'All entries', selected: true},
      {type: 'link', label: 'Singles', selected: false},
      {
        type: 'group',
        heading: 'Channels',
        items: [{type: 'link', label: 'Posts', selected: false}],
      },
    ]);
  });

  it('keeps the menu of a crumb that isn’t a link', () => {
    // The element index's site crumb: a switcher for something the nav has no
    // level for. It carries no href precisely so its sites aren't replaced by
    // whichever nav level its URL would have landed in.
    const items = [
      {type: 'link' as const, label: 'English', href: '/admin/content/entries'},
      {
        type: 'link' as const,
        label: 'French',
        href: '/admin/content/entries?site=fr',
      },
    ];
    const [crumb] = withNavCrumbMenus([{label: 'English', items}], NAV);

    expect(crumb!.items).toBe(items);
  });

  it('leaves a section that is nothing but its sources to the source crumb', () => {
    const [entries, all] = withNavCrumbMenus(
      [
        {label: 'Entries', href: '/admin/content/entries'},
        {
          label: 'All entries',
          href: '/admin/content/entries',
          items: [{type: 'link', label: 'All entries', href: '/x'}],
        },
      ],
      NAV,
      '/admin/content/entries'
    );

    expect(entries!.items).toBeUndefined();
    expect(all!.items).toHaveLength(3);
  });

  describe('in a plugin’s section', () => {
    const COMMERCE = navCrumbItem({
      label: 'Commerce',
      href: '/admin/commerce',
      subnav: [
        navCrumbItem({label: 'Orders', href: '/admin/commerce/orders'}),
        navCrumbItem({label: 'All products', href: '/admin/commerce/products'}),
        navCrumbItem({
          label: 'Product Types',
          href: null,
          group: true,
          subnav: [
            navCrumbItem({
              label: 'Widgets',
              href: '/admin/commerce/products/widgets',
            }),
            navCrumbItem({
              label: 'Gizmos',
              href: '/admin/commerce/products/gizmos',
            }),
          ],
        }),
        navCrumbItem({label: 'Inventory', href: '/admin/commerce/inventory'}),
      ],
    });
    const PLUGIN_NAV = [navCrumbItem({label: 'Dashboard'}), COMMERCE];
    const SOURCES = [
      {type: 'link' as const, label: 'All products', href: '/x'},
    ];

    const productCrumbs = (current: string): Array<BreadcrumbItem> => [
      {label: 'Commerce', href: '/admin/commerce'},
      {label: 'Products', href: '/admin/commerce/products'},
      {label: 'Current', href: current, items: SOURCES},
    ];

    it('gives the section’s crumb the whole section', () => {
      const [commerce] = withNavCrumbMenus(
        productCrumbs('/admin/commerce/products/gizmos'),
        PLUGIN_NAV,
        '/admin/commerce/products/gizmos'
      );

      expect(commerce!.items).toMatchObject([
        {type: 'link', label: 'Orders', selected: false},
        {type: 'link', label: 'All products', selected: false},
        {
          type: 'group',
          heading: 'Product Types',
          items: [
            {type: 'link', label: 'Widgets', selected: false},
            {type: 'link', label: 'Gizmos', selected: true},
          ],
        },
        {type: 'link', label: 'Inventory', selected: false},
      ]);
    });

    it('limits a source crumb to what sits under its index', () => {
      const [, products, source] = withNavCrumbMenus(
        productCrumbs('/admin/commerce/products'),
        PLUGIN_NAV,
        '/admin/commerce/products'
      );

      expect(products!.items).toBeUndefined();
      expect(source!.items).toMatchObject([
        {type: 'link', label: 'All products', selected: true},
        {
          type: 'group',
          heading: 'Product Types',
          items: [
            {type: 'link', label: 'Widgets', selected: false},
            {type: 'link', label: 'Gizmos', selected: false},
          ],
        },
      ]);
    });

    it('gives the section’s crumb its menu on a screen with no switchers', () => {
      const [commerce, inventory] = withNavCrumbMenus(
        [
          {label: 'Commerce', href: '/admin/commerce'},
          {label: 'Inventory', href: '/admin/commerce/inventory'},
        ],
        PLUGIN_NAV,
        '/admin/commerce/inventory'
      );

      expect(commerce!.items).toHaveLength(4);
      expect(inventory!.items).toBeUndefined();
    });
  });
});
