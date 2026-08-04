import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {ifDefined} from 'lit/directives/if-defined.js';

import './switch.js';

const meta = {
  title: 'Controls/Switch',
  component: 'craft-switch',
  args: {
    label: 'Enable feature',
    helpText: '',
    size: 'medium',
    disabled: false,
    checked: false,
    labelSrOnly: false,
    onLabel: undefined,
    offLabel: undefined,
  },
  argTypes: {
    size: {
      control: 'select',
      options: ['small', 'medium'],
    },
    onLabel: {
      control: 'text',
    },
    offLabel: {
      control: 'text',
    },
  },
  render: ({
    label,
    helpText,
    size,
    disabled,
    checked,
    labelSrOnly,
    onLabel,
    offLabel,
  }) => {
    return html`<craft-switch
      label=${label}
      help-text=${helpText}
      size=${size}
      ?disabled=${disabled}
      ?checked=${checked}
      ?label-sr-only=${labelSrOnly}
      on-label=${ifDefined(onLabel || undefined)}
      off-label=${ifDefined(offLabel || undefined)}
    ></craft-switch>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {
  args: {},
};

export const Checked: Story = {
  args: {
    checked: true,
  },
};

export const WithHelpText: Story = {
  args: {
    helpText: 'This toggles the feature on or off',
  },
};

export const Sizes: Story = {
  render: () => html`
    <div style="display: flex; align-items: center; gap: 24px;">
      <craft-switch label="Small" size="small"></craft-switch>
      <craft-switch label="Medium" size="medium"></craft-switch>
    </div>
  `,
};

export const Disabled: Story = {
  args: {
    disabled: true,
  },
};

export const DisabledChecked: Story = {
  args: {
    disabled: true,
    checked: true,
  },
};

export const LabelSrOnly: Story = {
  args: {
    labelSrOnly: true,
  },
};

export const MultipleLabels: Story = {
  args: {
    onLabel: 'On',
    offLabel: 'Off',
  },
};

export const Indeterminate: Story = {
  render: () => html`
    <craft-switch label="Mixed state" indeterminate></craft-switch>
  `,
};
