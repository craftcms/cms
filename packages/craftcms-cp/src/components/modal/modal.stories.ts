import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect} from 'storybook/test';

import {html} from 'lit';

import './modal.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Modal',
  component: 'craft-modal',
  args: {},
  parameters: {
    layout: 'centered',
  },
  render: function (args) {
    return html`
      <craft-modal>
        <craft-button slot="invoker">Open Modal</craft-button>
        <div slot="content">
          This is the body content
          <craft-button
            @click="${(event: Event) =>
              event.target?.dispatchEvent(
                new Event('close-overlay', {bubbles: true})
              )}"
            >Close</craft-button
          >
        </div>
      </craft-modal>
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
  play: async ({canvas, userEvent}) => {
    const openBtn = canvas.getByShadowText(/Open Modal/i);
    const closeBtn = canvas.getByText(/Close/i);
    await userEvent.click(openBtn);

    const modal = canvas.getByShadowRole('dialog');
    await expect(modal).toHaveClass('overlays__overlay');
    await userEvent.click(closeBtn);
    await expect(modal).not.toHaveClass('overlays__overlay');
    await expect(openBtn).toHaveFocus();
  },
};
