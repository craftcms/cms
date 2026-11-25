import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './metadata-list.js';
import '../badge/badge.js';
import type CraftMetadataList from './metadata-list.js';

const {args, argTypes} = getStorybookHelpers<CraftMetadataList>(
  'craft-metadata-list'
);

type MetadataListArgs = CraftMetadataList & typeof args;

const facts = html`
  <dl>
    <div>
      <dt>ID</dt>
      <dd>2</dd>
    </div>
    <div>
      <dt>Status</dt>
      <dd><craft-badge fill="green">Live</craft-badge></dd>
    </div>
    <div>
      <dt>Created at</dt>
      <dd>11/19/2026, 9:10 AM PST</dd>
    </div>
    <div>
      <dt>Updated at</dt>
      <dd>11/19/2026, 9:10 AM PST</dd>
    </div>
  </dl>
`;

const meta = {
  title: 'Components/Metadata List',
  component: 'craft-metadata-list',
  args,
  argTypes,
  render: ({layout}) => html`
    <craft-metadata-list layout=${layout}>${facts}</craft-metadata-list>
  `,
} satisfies Meta<MetadataListArgs>;

export default meta;
type Story = StoryObj<MetadataListArgs>;

/** Labels beside their values where there's room, above them where there isn't. */
export const Default: Story = {};

/** Each label above its value. */
export const Stacked: Story = {args: {layout: 'stacked'}};

/** Each label beside its value, however narrow the list. */
export const Inline: Story = {args: {layout: 'inline'}};

/** The items side by side, each label above its value. */
export const Row: Story = {args: {layout: 'row'}};

/** The same list in a narrow sidebar and a wide one: `auto` follows the list's width. */
export const Auto: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: grid; gap: 2rem;">
      <craft-metadata-list style="width: 14rem;">${facts}</craft-metadata-list>
      <craft-metadata-list style="width: 24rem;">${facts}</craft-metadata-list>
    </div>
  `,
};
