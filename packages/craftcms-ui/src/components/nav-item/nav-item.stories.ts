import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import '../icon/icon.js';
import '../nav-list/nav-list.js';
import '../button/button.js';
import './nav-item.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Nav Item',
  component: 'craft-nav-item',
  argTypes: {},
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
  render: ({active, indicator}) => {
    return html`
      <craft-nav-list>
        <craft-nav-item
          icon="gauge"
          ?active="${active}"
          ?indicator="${indicator}"
          >Dashboard</craft-nav-item
        >
      </craft-nav-list>
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;
// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const Active: Story = {
  args: {
    active: true,
  },
};

export const WithIndicator: Story = {
  args: {
    indicator: true,
  },
};

export const WithChildren: Story = {
  args: {},
  render(args) {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item icon="code">
          GraphQL
          <craft-nav-list slot="subnav">
            <craft-nav-item>Schemas</craft-nav-item>
            <craft-nav-item active>Tokens</craft-nav-item>
            <craft-nav-item external>GraphiQL</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};

/**
 * `group` renders the item as a non-collapsible semantic grouping: no
 * disclosure toggle, and the subnav stays open. Contrast with `WithChildren`,
 * which collapses. A `group` heading has no `href`, so it renders as a plain
 * `<span>` rather than a link.
 */
export const Group: Story = {
  args: {},
  render(args) {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item group>
          Account Security
          <craft-nav-list slot="subnav">
            <craft-nav-item href="#password" active
              >Password &amp; Verification</craft-nav-item
            >
            <craft-nav-item href="#passkeys">Passkeys</craft-nav-item>
            <craft-nav-item href="#providers">Sign-in Providers</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};

/**
 * `subnav-display="flyout"` moves the subnav into a popover beside the item,
 * opened by hover or by the chevron — never by focus, which would put every
 * child of every branch in the tab order. The chevron carries `aria-expanded`
 * and `aria-controls`; an item with no `href` renders as a button rather than
 * a span, so it can be reached by keyboard too.
 */
export const Flyout: Story = {
  args: {},
  render(args) {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item icon="code" href="#graphql" subnav-display="flyout">
          GraphQL
          <craft-nav-list slot="subnav">
            <craft-nav-item href="#schemas">Schemas</craft-nav-item>
            <craft-nav-item href="#tokens">Tokens</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
        <craft-nav-item icon="gear" subnav-display="flyout">
          Administration
          <craft-nav-list slot="subnav">
            <craft-nav-item href="#users">Users</craft-nav-item>
            <craft-nav-item href="#plugins">Plugins</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};

/** Collapsed to a rail there is nowhere to indent, so every subnav flies out. */
export const IconOnly: Story = {
  args: {},
  render(args) {
    return html`
      <craft-nav-list>
        <craft-nav-item icon="code" href="#graphql" icon-only>
          GraphQL
          <craft-nav-list slot="subnav">
            <craft-nav-item href="#schemas">Schemas</craft-nav-item>
            <craft-nav-item href="#tokens">Tokens</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
        <craft-nav-item icon="gear" href="#settings" icon-only>
          Settings
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};

/**
 * The branch you're in, collapsed. A rail flies out by default, but
 * `subnav-display="inline"` indents in place instead — and since there's no
 * room for labels, an icon-less child stands its first letter in for one. A
 * heading becomes the rule between runs, and the chevron under the parent
 * shuts the branch without expanding the nav.
 */
export const IconOnlyInline: Story = {
  args: {},
  render(args) {
    return html`
      <craft-nav-list>
        <craft-nav-item
          icon="newspaper"
          href="#entries"
          icon-only
          subnav-display="inline"
          active
        >
          Entries
          <craft-nav-list slot="subnav">
            <craft-nav-item href="#all" icon-only active>
              All Entries
            </craft-nav-item>
            <craft-nav-item href="#singles" icon-only>Singles</craft-nav-item>
            <craft-nav-item group icon-only>
              Channels
              <craft-nav-list slot="subnav">
                <craft-nav-item href="#posts" icon-only>Posts</craft-nav-item>
              </craft-nav-list>
            </craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
        <craft-nav-item icon="gear" href="#settings" icon-only>
          Settings
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};

/** Toggle rendered in the prefix instead of the suffix. */
export const TogglePositionPrefix: Story = {
  args: {},
  render(args) {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item toggle-position="prefix">
          GraphQL
          <craft-nav-list slot="subnav">
            <craft-nav-item>Schemas</craft-nav-item>
            <craft-nav-item active>Tokens</craft-nav-item>
            <craft-nav-item external>GraphiQL</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};

/** No label: no toggle, and the subnav stays expanded. */
export const SubnavWithoutLabel: Story = {
  args: {},
  render(args) {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item>
          <craft-nav-list slot="subnav">
            <craft-nav-item>Schemas</craft-nav-item>
            <craft-nav-item active>Tokens</craft-nav-item>
            <craft-nav-item external>GraphiQL</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};
