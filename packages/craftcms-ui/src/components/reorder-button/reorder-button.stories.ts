import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './reorder-button.js';

const meta = {
  title: 'Components/Reorder Button',
  component: 'craft-reorder-button',
  parameters: {
    layout: 'centered',
  },
  args: {
    label: 'Reorder',
    position: 'middle',
    variant: 'neutral',
    orientation: 'vertical',
    disabled: false,
  },
  argTypes: {
    position: {
      control: {type: 'select'},
      options: ['first', 'middle', 'last'],
    },
    orientation: {
      control: {type: 'select'},
      options: ['vertical', 'horizontal'],
    },
  },
  render: (args) => html`
    <craft-reorder-button
      label="${args.label}"
      position="${args.position}"
      variant="${args.variant}"
      orientation="${args.orientation}"
      ?disabled="${args.disabled}"
      @reorder="${(e: CustomEvent<{direction: 'up' | 'down'}>) =>
        console.log('reorder', e.detail.direction)}"
    ></craft-reorder-button>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {
  args: {},
};

export const First: Story = {
  name: 'First (move-up disabled)',
  args: {position: 'first'},
};

export const Last: Story = {
  name: 'Last (move-down disabled)',
  args: {position: 'last'},
};

export const Horizontal: Story = {
  name: 'Horizontal (move forward/backward)',
  args: {orientation: 'horizontal'},
};

export const Disabled: Story = {
  args: {disabled: true},
};
