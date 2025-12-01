import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect} from 'storybook/test';
import {html} from 'lit';

import './disclosure.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Functional/Disclosure',
  component: 'craft-disclosure',
  argTypes: {
    state: {
      control: {
        type: 'select',
      },
      options: ['collapsed', 'expanded'],
      defaultValue: null,
    },
  },
  play: async ({canvas, userEvent}) => {
    const trigger = canvas.getByTestId('trigger');
    await userEvent.click(trigger);
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    await expect(canvas.getByTestId('target')).toHaveAttribute(
      'data-state',
      'expanded'
    );
  },
  render: (args) => html`
    <craft-disclosure state="${args.state}" ${
      args.cookieName ? `cookie-name="${args.cookieName}` : ''
    }">
      <button type="button" aria-controls="target" data-testid="trigger">Toggle</button>
      <div id="target" data-testid="target">This will toggle</div>
    </craft-disclosure>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Basic: Story = {
  args: {},
};

export const Expanded: Story = {
  args: {
    state: 'expanded',
  },
  play: async ({canvas, userEvent}) => {
    await expect(canvas.getByTestId('trigger')).toHaveAttribute(
      'aria-expanded',
      'true'
    );
  },
};

export const Persistant: Story = {
  args: {
    cookieName: 'persistent-disclosure',
  },
};
