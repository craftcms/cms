import {expect, it} from 'vite-plus/test';

import {navItemActions} from './navActions';
import {node} from '@/common/components/NavTree.fixture';
import type {ActionItemGroup, ActionItemLink} from '@/common/types';

it('carries a nav item’s children across as descriptors', () => {
  const entries = navItemActions([
    node('Entries', {
      href: '/admin/content/entries',
      subnav: [node('Blog', {href: '/admin/content/entries/blog'})],
    }),
  ])[0] as ActionItemLink;

  // A menu draws a flat list and ignores these; a nav draws them. What neither
  // has to do is consult a second description of the same nav to find them.
  expect(entries.subnav).toHaveLength(1);
  expect((entries.subnav![0] as ActionItemLink).href).toBe(
    '/admin/content/entries/blog'
  );
});

it('carries children all the way down, not just one level', () => {
  const content = navItemActions([
    node('Content', {
      href: '/admin/content',
      subnav: [
        node('Entries', {
          href: '/admin/content/entries',
          subnav: [node('Blog', {href: '/admin/content/entries/blog'})],
        }),
      ],
    }),
  ])[0] as ActionItemLink;

  const entries = content.subnav![0] as ActionItemLink;

  expect((entries.subnav![0] as ActionItemLink).label).toBe('Blog');
});

it('turns a group into a heading over its members', () => {
  const channels = navItemActions([
    node('Channels', {
      group: true,
      subnav: [node('Blog', {href: '/admin/content/entries/blog'})],
    }),
  ])[0] as ActionItemGroup;

  expect(channels.type).toBe('group');
  expect(channels.heading).toBe('Channels');
  expect(channels.items).toHaveLength(1);
});

it('leaves a leaf without an empty children list', () => {
  const dashboard = navItemActions([
    node('Dashboard', {href: '/admin/dashboard'}),
  ])[0] as ActionItemLink;

  // Absent rather than empty, so `subnav` reads as "has children" without
  // every leaf in a menu carrying an array that means nothing.
  expect(dashboard.subnav).toBeUndefined();
});

it('describes an item with no destination as something you cannot follow', () => {
  const administration = navItemActions([
    node('Administration', {subnav: [node('Users', {href: '/admin/users'})]}),
  ])[0]!;

  // Still in the list — it heads a branch — but not a link.
  expect(administration.type).not.toBe('link');
  expect((administration as {disabled?: boolean}).disabled).toBe(true);
});
