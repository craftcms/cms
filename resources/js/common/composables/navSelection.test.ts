import {expect, it} from 'vite-plus/test';

import {withNavBadges, withNavSelection} from './navSelection';
import {node} from '@/common/components/nav.fixture';

const tree = () => [
  node('Dashboard', {href: '/admin/dashboard'}),
  node('GraphQL', {
    href: '/admin/graphql',
    id: 'nav-graphql',
    subnav: [
      node('Schemas', {href: '/admin/graphql/schemas'}),
      node('Tokens', {href: '/admin/graphql/tokens'}),
    ],
  }),
  node('Utilities', {href: '/admin/utilities', id: 'nav-utilities'}),
];

const labelsSelected = (
  items: Array<CraftCms.Cms.Cp.Data.ActionItem>
): Array<string | null> =>
  items.flatMap((item) => [
    ...(item.selected ? [item.label] : []),
    ...(Array.isArray(item.subnav) ? labelsSelected(item.subnav) : []),
  ]);

it('marks the whole trail, not just the leaf', function () {
  const selected = withNavSelection(tree(), '/admin/graphql/tokens');

  // The ancestor has to be marked too, or the branch wouldn't expand.
  expect(labelsSelected(selected)).toEqual(['GraphQL', 'Tokens']);
});

it('matches a section by prefix, so a page inside it still counts', function () {
  const selected = withNavSelection(tree(), '/admin/utilities/system-report');

  expect(labelsSelected(selected)).toEqual(['Utilities']);
});

it('gives the trail to the deepest match rather than the first', function () {
  // `/admin/graphql` prefixes the parent as well as the child, and the parent
  // comes first. Marking only it would leave the child unhighlighted.
  const selected = withNavSelection(tree(), '/admin/graphql/schemas');

  expect(labelsSelected(selected)).toEqual(['GraphQL', 'Schemas']);
});

it('picks the source over the index that sits beside it', function () {
  // This is how a real sources subnav is shaped: the index is a sibling of the
  // sources, not their parent, and its path prefixes every one of them. Taking
  // the first match handed it the selection on every source page, and the
  // source you were actually on never lit up.
  const items = [
    node('Entries', {
      href: '/admin/content/entries',
      subnav: [
        node('All Entries', {href: '/admin/content/entries'}),
        node('Channels', {
          group: true,
          subnav: [node('Blog', {href: '/admin/content/entries/blog'})],
        }),
      ],
    }),
  ];

  expect(
    labelsSelected(withNavSelection(items, '/admin/content/entries/blog'))
  ).toEqual(['Entries', 'Channels', 'Blog']);
});

it('falls back to the index when no source matches', function () {
  const items = [
    node('Entries', {
      href: '/admin/content/entries',
      subnav: [
        node('All Entries', {href: '/admin/content/entries'}),
        node('Blog', {href: '/admin/content/entries/blog'}),
      ],
    }),
  ];

  expect(
    labelsSelected(withNavSelection(items, '/admin/content/entries'))
  ).toEqual(['Entries', 'All Entries']);
});

it('prefers the longer of two matching siblings', function () {
  // Both prefix the url and neither has a matching child, so the tie is broken
  // on specificity rather than on which happens to come first.
  const items = [
    node('Users', {href: '/admin/settings/users'}),
    node('User Groups', {href: '/admin/settings/users/groups'}),
  ];

  expect(
    labelsSelected(withNavSelection(items, '/admin/settings/users/groups/2'))
  ).toEqual(['User Groups']);
});

it('selects nothing for a page the nav does not cover', function () {
  expect(labelsSelected(withNavSelection(tree(), '/admin/nowhere'))).toEqual(
    []
  );
});

it('reads your own account as Users, where the nav points', function () {
  const items = [node('Users', {href: '/admin/users'})];

  expect(labelsSelected(withNavSelection(items, '/admin/myaccount'))).toEqual([
    'Users',
  ]);
});

it('matches an absolute href against a relative url', function () {
  const items = [node('Users', {href: 'http://example.test/admin/users'})];

  expect(labelsSelected(withNavSelection(items, '/admin/users/5'))).toEqual([
    'Users',
  ]);
});

it('leaves the source tree alone', function () {
  // The tree is a once-prop the client keeps across visits, so writing
  // selection into it would strand the previous page's trail on the next one.
  const items = tree();

  withNavSelection(items, '/admin/graphql/tokens');

  expect(labelsSelected(items)).toEqual([]);
});

it('fills badge counts in by id', function () {
  const badged = withNavBadges(tree(), {'nav-utilities': 3});
  const utilities = badged.find((item) => item.label === 'Utilities');
  const graphql = badged.find((item) => item.label === 'GraphQL');

  expect(utilities?.badgeCount).toBe(3);
  expect(graphql?.badgeCount).toBe(0);
});
