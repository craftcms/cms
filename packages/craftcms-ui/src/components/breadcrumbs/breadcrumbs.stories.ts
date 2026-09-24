import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './breadcrumbs.js';
import '../breadcrumb-item/breadcrumb-item.js';
import type CraftBreadcrumbs from './breadcrumbs.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `breadcrumbs.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftBreadcrumbs>('craft-breadcrumbs');

type BreadcrumbsArgs = CraftBreadcrumbs & typeof args;

const TRAIL = `
  <craft-breadcrumb-item href="#">Site Name</craft-breadcrumb-item>
  <craft-breadcrumb-item href="#">Entries</craft-breadcrumb-item>
  <craft-breadcrumb-item href="#">Entry Type</craft-breadcrumb-item>
  <craft-breadcrumb-item href="#">Current Entry</craft-breadcrumb-item>
`;

const meta = {
  title: 'Components/Breadcrumbs',
  component: 'craft-breadcrumbs',
  args: {...args, 'default-slot': TRAIL},
  argTypes,
  // Render from args alone so every control — attributes and slot — drives the
  // story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<BreadcrumbsArgs>;

export default meta;
type Story = StoryObj<BreadcrumbsArgs>;

/** The trail is `craft-breadcrumb-item` children; the list draws the rest. */
export const Default: Story = {};

/**
 * The trail collapses from the middle when it runs out of room. Drag the
 * handle to see it.
 */
export const Resizable: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div class="resizable-container">
      <craft-breadcrumbs>
        <craft-breadcrumb-item href="#">Site Name</craft-breadcrumb-item>
        <craft-breadcrumb-item href="#">Entries</craft-breadcrumb-item>
        <craft-breadcrumb-item href="#">Entry Type</craft-breadcrumb-item>
        <craft-breadcrumb-item href="#">Current Entry</craft-breadcrumb-item>
      </craft-breadcrumbs>
    </div>
  `,
};
