import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './badge-indicator.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Badge Indicator',
  component: 'craft-badge-indicator',
  args: {
    variant: 'primary',
    altText: 'Has Notifications',
    badgeCount: null,
  },
  argTypes: {
    badgeCount: {
      control: {
        type: 'number',
      },
    },
    variant: {
      options: ['primary', 'secondary', 'inverse'],
      control: {
        type: 'radio',
      },
    },
    altText: {
      control: {
        type: 'text',
      },
    },
  },
  render: (args) => html`
    <craft-badge-indicator
      .badgeCount="${args.badgeCount}"
      .badgeCountSuffix="${args.badgeCountSuffix}"
      .altText="${args.altText}"
      .variant="${args.variant}"
    ></craft-badge-indicator>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args

export const Dot: Story = {
  name: 'Dot',
  parameters: {
    controls: {exclude: ['badgeCount', 'badgeCountSuffix']},
  },
};

export const Numbered: Story = {
  name: 'Numbered',
  args: {
    altText: null,
    badgeCount: 5,
    badgeCountSuffix: 'updates',
  },
  parameters: {
    controls: {exclude: ['altText']},
  },
};

export const Primary: Story = {
  name: 'Primary',
  parameters: {
    controls: {exclude: ['badgeCount', 'badgeCountSuffix']},
  },
};

export const Secondary: Story = {
  name: 'Secondary',
  args: {
    variant: 'secondary',
  },
  parameters: {
    controls: {exclude: ['badgeCount', 'badgeCountSuffix']},
  },
};

export const Inverse: Story = {
  name: 'Inverse',
  args: {
    variant: 'inverse',
  },
  parameters: {
    controls: {exclude: ['badgeCount', 'badgeCountSuffix']},
  },
};
