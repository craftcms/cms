import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import {html} from 'lit';

import '../icon/icon.js';
import '../nav-list/nav-list.js';
import '../button/button.js';
import './nav-item.js';
import type CraftNavItem from './nav-item.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `nav-item.ts` surfaces it here without touching this file.
 */
const {args, argTypes} = getStorybookHelpers<CraftNavItem>('craft-nav-item');

type CraftNavItemArgs = CraftNavItem & typeof args;

const meta = {
  title: 'Components/Nav Item',
  component: 'craft-nav-item',
  args,
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
} satisfies Meta<CraftNavItemArgs>;

export default meta;
type Story = StoryObj<CraftNavItemArgs>;
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
