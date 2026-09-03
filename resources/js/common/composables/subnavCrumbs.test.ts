import {describe, expect, it} from 'vite-plus/test';
import {subnavCrumbs, withSubnavCrumbs} from './subnavCrumbs';

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
