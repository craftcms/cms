import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import '../icon/icon.js';
import '../nav-item/nav-item.js';
import './nav-list.js';
import type CraftNavList from './nav-list.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `nav-list.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftNavList>('craft-nav-list');

type NavListArgs = CraftNavList & typeof args;

const meta = {
  title: 'Components/Nav List',
  component: 'craft-nav-list',
  args: {
    ...args,
    'default-slot': `
      <craft-nav-item icon="gauge" active>Dashboard</craft-nav-item>
      <craft-nav-item icon="pencil">Entries</craft-nav-item>
      <craft-nav-item icon="image">Assets</craft-nav-item>
      <craft-nav-item icon="users">Users</craft-nav-item>
    `,
  },
  argTypes,
  parameters: {
    a11y: {
      config: {
        rules: [
          {
            id: 'list',
            enabled: false,
          },
          {
            id: 'listitem',
            enabled: false,
          },
        ],
      },
    },
  },
  // Render from args alone so the slot control drives the story. The
  // composition stories below supply their own markup instead, and disable the
  // controls that no longer reach them.
  render: (args) => template(args),
} satisfies Meta<NavListArgs>;

export default meta;
type Story = StoryObj<NavListArgs>;

/** A flat list of items. */
export const Default: Story = {};

/** Nested in a nav item's `subnav` slot for a collapsible hierarchy. */
export const Nested: Story = {
  parameters: {controls: {disable: true}},
  render() {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item icon="gauge" active>Dashboard</craft-nav-item>
        <craft-nav-item icon="code">
          GraphQL
          <craft-nav-list slot="subnav">
            <craft-nav-item>Schemas</craft-nav-item>
            <craft-nav-item active>Tokens</craft-nav-item>
            <craft-nav-item external>GraphiQL</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
        <craft-nav-item icon="gear">Settings</craft-nav-item>
      </craft-nav-list>
    `;
  },
};

/**
 * The element-index source nav (`ElementSources.vue`). A flat source list is
 * normalized so each heading absorbs the sources that follow it into its
 * `subnav` slot:
 *
 * - Sources before the first heading stay at the top level (e.g. the active
 *   "All entries").
 * - A heading renders as a static, `initial-state="open"` item whose label is a
 *   `<span class="text-xs font-bold">` — so a heading with no text renders an
 *   empty (and non-collapsible) container that still groups its children
 *   (e.g. "Singles").
 * - Grouped children carry a `data-group` attribute naming their heading.
 */
export const ContentSources: Story = {
  parameters: {controls: {disable: true}},
  render() {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item href="#all" active>All entries</craft-nav-item>
        <craft-nav-item initial-state="open">
          <span class="text-xs font-bold">Channels</span>
          <craft-nav-list slot="subnav">
            <craft-nav-item href="#news" data-group="Channels"
              >News</craft-nav-item
            >
            <craft-nav-item href="#blog" data-group="Channels"
              >Blog</craft-nav-item
            >
          </craft-nav-list>
        </craft-nav-item>
        <craft-nav-item initial-state="open">
          <span class="text-xs font-bold">Structures</span>
          <craft-nav-list slot="subnav">
            <craft-nav-item href="#docs" data-group="Structures"
              >Documentation</craft-nav-item
            >
          </craft-nav-list>
        </craft-nav-item>
        <craft-nav-item initial-state="open">
          <craft-nav-list slot="subnav">
            <craft-nav-item href="#singles" data-group=""
              >Singles</craft-nav-item
            >
          </craft-nav-list>
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};

/**
 * The CP secondary nav (`SecondaryNav.vue`), as used on the My Account screen.
 * Every item is `block flush`: `flush` pulls the item's padding back for the
 * sidebar's alignment, and `block` is a host-level styling hook the wrapper's
 * scoped CSS targets (it isn't a `craft-nav-item` property). A subnav group is
 * an item whose label is a `<span class="text-xs font-bold">` heading, with its
 * links in the `subnav` slot; plain items sit at the top level. "Account
 * Security" uses `group` (per `EditUserTrait`), so it renders non-collapsible:
 * no toggle, subnav always open.
 */
export const SecondaryNav: Story = {
  parameters: {controls: {disable: true}},
  render() {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item href="#profile" active block flush
          >Profile</craft-nav-item
        >
        <craft-nav-item href="#permissions" block flush
          >Permissions</craft-nav-item
        >
        <craft-nav-item href="#preferences" block flush
          >Preferences</craft-nav-item
        >
        <craft-nav-item href="#addresses" block flush>Addresses</craft-nav-item>
        <craft-nav-item initial-state="open" block flush group>
          <span class="text-xs font-bold">Account Security</span>
          <craft-nav-list slot="subnav">
            <craft-nav-item href="#password" block flush
              >Password &amp; Verification</craft-nav-item
            >
            <craft-nav-item href="#passkeys" block flush
              >Passkeys</craft-nav-item
            >
            <craft-nav-item href="#sign-in-providers" block flush
              >Sign-in Providers</craft-nav-item
            >
          </craft-nav-list>
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};

/**
 * A collapsed nav where each item renders as `icon-only`. The item's label
 * moves into a tooltip (shown on hover/focus), so every item still needs both
 * an `icon` and slotted label text.
 */
export const IconOnly: Story = {
  parameters: {controls: {disable: true}},
  render() {
    return html`
      <craft-nav-list>
        <craft-nav-item icon="gauge" icon-only active>Dashboard</craft-nav-item>
        <craft-nav-item icon="pencil" icon-only>Entries</craft-nav-item>
        <craft-nav-item icon="image" icon-only>Assets</craft-nav-item>
        <craft-nav-item icon="users" icon-only>Users</craft-nav-item>
        <craft-nav-item icon="gear" icon-only>Settings</craft-nav-item>
      </craft-nav-list>
    `;
  },
};
