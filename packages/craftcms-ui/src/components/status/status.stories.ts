import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './status.js';
import type CraftStatus from './status.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftStatus>('craft-status');

type StatusArgs = CraftStatus & typeof args;

const STATUSES = ['live', 'pending', 'expired', 'disabled', 'enabled'] as const;

const meta = {
  title: 'Components/Status',
  component: 'craft-status',
  args: {...args, status: 'live'},
  argTypes,
  parameters: {layout: 'centered'},
  render: (args) => template(args),
} satisfies Meta<StatusArgs>;

export default meta;
type Story = StoryObj<StatusArgs>;

/** A dot standing for one state. */
export const Default: Story = {};

/** Each state renders in its own colour. */
export const AllStatuses: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.5rem">
      ${STATUSES.map(
        (status) => html`
          <span style="display: flex; align-items: center; gap: 0.5rem">
            <craft-status status="${status}"></craft-status> ${status}
          </span>
        `
      )}
    </div>
  `,
};

/** With no status, the dot is neutral. */
export const NoStatus: Story = {
  args: {status: null},
};

/**
 * `label` replaces the announced name. Without one, the status supplies a
 * translated "Status: live".
 */
export const CustomLabel: Story = {
  args: {status: 'pending', label: 'Awaiting review'},
};

/** Where it usually sits — beside the thing it describes. */
export const InContext: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <span style="display: inline-flex; align-items: center; gap: 0.5rem">
      <craft-status status="live"></craft-status> Homepage
    </span>
  `,
};
