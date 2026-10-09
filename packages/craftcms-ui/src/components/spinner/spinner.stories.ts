import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './spinner.js';
import '../button/button.js';
import type CraftSpinner from './spinner.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftSpinner>('craft-spinner');

type SpinnerArgs = CraftSpinner & typeof args;

const meta = {
  title: 'Components/Spinner',
  component: 'craft-spinner',
  args: {...args, 'default-slot': 'Loading entries'},
  argTypes,
  parameters: {layout: 'centered'},
  render: (args) => template(args),
} satisfies Meta<SpinnerArgs>;

export default meta;
type Story = StoryObj<SpinnerArgs>;

/** The slotted text is announced but not shown. */
export const Default: Story = {};

/** `visible` hides the spinner without removing it from the layout. */
export const Hidden: Story = {
  args: {visible: false},
};

/**
 * Because a hidden spinner still occupies its space, content around it does
 * not jump as work starts and stops.
 */
export const HoldsItsSpace: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; align-items: center; gap: 0.5rem">
      Saving
      <craft-spinner visible="${false}">Saving</craft-spinner>
      — the spinner is hidden, but the gap it leaves is not.
    </div>
  `,
};

/** Spinners inherit the surrounding font size. */
export const Sizes: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; gap: 1.5rem; align-items: center">
      <craft-spinner>Loading</craft-spinner>
      <span style="font-size: 1.5rem"
        ><craft-spinner>Loading</craft-spinner></span
      >
      <span style="font-size: 2.5rem"
        ><craft-spinner>Loading</craft-spinner></span
      >
    </div>
  `,
};
