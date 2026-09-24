import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './visually-hidden.js';
import '../button/button.js';
import '../icon/icon.js';
import type CraftVisuallyHidden from './visually-hidden.js';

const {args, argTypes, template} = getStorybookHelpers<CraftVisuallyHidden>(
  'craft-visually-hidden'
);

type VisuallyHiddenArgs = CraftVisuallyHidden & typeof args;

const meta = {
  title: 'Components/Visually Hidden',
  component: 'craft-visually-hidden',
  args: {...args, 'default-slot': 'Announced but not shown'},
  argTypes,
  render: (args) => template(args),
} satisfies Meta<VisuallyHiddenArgs>;

export default meta;
type Story = StoryObj<VisuallyHiddenArgs>;

/** There is text here — it is just not visible. */
export const Default: Story = {};

/** `debug` reveals the content, for checking what would be announced. */
export const Debug: Story = {
  args: {debug: true},
};

/** Naming an icon-only control without showing the label twice. */
export const InContext: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-button>
      <craft-icon name="trash"></craft-icon>
      <craft-visually-hidden>Delete entry</craft-visually-hidden>
    </craft-button>
  `,
};
