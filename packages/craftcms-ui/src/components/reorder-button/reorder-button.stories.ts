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
    nested: false,
    canIndent: false,
    canOutdent: false,
  },
  argTypes: {
    position: {
      control: {type: 'select'},
      options: ['first', 'middle', 'last', 'only'],
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
      ?nested="${args.nested}"
      ?can-indent="${args.canIndent}"
      ?can-outdent="${args.canOutdent}"
      @reorder="${(e: CustomEvent<{direction: string}>) =>
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

export const Nested: Story = {
  name: 'Nested (indent/outdent)',
  args: {nested: true, canIndent: true, canOutdent: true},
};
