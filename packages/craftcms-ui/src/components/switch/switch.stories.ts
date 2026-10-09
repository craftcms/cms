import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './switch.js';
import type CraftSwitch from './switch.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `switch.ts` surfaces it here without touching this file.
 *
 * Lion's own `label` and `help-text` are slots here, so they are set through
 * the `label-slot` and `help-text-slot` args rather than as attributes.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftSwitch>('craft-switch');

type SwitchArgs = CraftSwitch & typeof args;

const meta = {
  title: 'Form Controls/Choice Controls/Switch',
  component: 'craft-switch',
  args: {...args, size: 'medium', 'label-slot': 'Enable feature'},
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<SwitchArgs>;

export default meta;
type Story = StoryObj<SwitchArgs>;

/** Off by default; clicking or pressing Space toggles it. */
export const Default: Story = {};

export const Checked: Story = {
  args: {checked: true},
};

export const WithHelpText: Story = {
  args: {'help-text-slot': 'This toggles the feature on or off.'},
};

/** The two sizes, side by side. */
export const Sizes: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; align-items: center; gap: 24px">
      <craft-switch size="small"
        ><label slot="label">Small</label></craft-switch
      >
      <craft-switch size="medium"
        ><label slot="label">Medium</label></craft-switch
      >
    </div>
  `,
};

export const Disabled: Story = {
  args: {disabled: true},
};

export const DisabledChecked: Story = {
  args: {disabled: true, checked: true},
};

/** `on-label` and `off-label` put the state in words beside the control. */
export const MultipleLabels: Story = {
  args: {'on-label': 'On', 'off-label': 'Off'},
};

/** The third state, for a switch standing in for a mixed group. */
export const Indeterminate: Story = {
  args: {indeterminate: true, 'label-slot': 'Mixed state'},
};
