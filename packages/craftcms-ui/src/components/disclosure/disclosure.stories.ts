import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect} from 'storybook/test';
import {html} from 'lit';

import './disclosure.js';

const meta = {
  title: 'Components/Disclosure',
  component: 'craft-disclosure',
  argTypes: {
    state: {
      control: {type: 'select'},
      options: ['collapsed', 'expanded'],
    },
  },
  render: (args) => html`
    <craft-disclosure state="${args.state || 'collapsed'}">
      <button type="button" aria-controls="disclosure-target">
        Toggle Content
      </button>
    </craft-disclosure>

    <div
      id="disclosure-target"
      data-testid="target"
      data-state="collapsed"
      style="overflow: hidden;"
    >
      <p>This content is revealed inline when the disclosure is expanded.</p>
      <p>It pushes subsequent content down in the normal document flow.</p>
    </div>

    <style>
      [data-state='collapsed'] {
        display: none;
      }
      [data-state='expanded'] {
        display: block;
      }
    </style>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Collapsed: Story = {
  args: {
    state: 'collapsed',
  },
  play: async ({canvas, userEvent}) => {
    const trigger = canvas.getByRole('button');
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');

    await userEvent.click(trigger);
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  },
};

export const Expanded: Story = {
  args: {
    state: 'expanded',
  },
  play: async ({canvas}) => {
    const trigger = canvas.getByRole('button');
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  },
};

export const Accordion: Story = {
  render: () => html`
    <style>
      [data-state='collapsed'] {
        display: none;
      }
      [data-state='expanded'] {
        display: block;
      }
    </style>

    <div>
      <craft-disclosure state="expanded">
        <button type="button" aria-controls="section-1">Section 1</button>
      </craft-disclosure>
      <div id="section-1" data-state="expanded">
        <p>Content for section 1.</p>
      </div>

      <craft-disclosure state="collapsed">
        <button type="button" aria-controls="section-2">Section 2</button>
      </craft-disclosure>
      <div id="section-2" data-state="collapsed">
        <p>Content for section 2.</p>
      </div>

      <craft-disclosure state="collapsed">
        <button type="button" aria-controls="section-3">Section 3</button>
      </craft-disclosure>
      <div id="section-3" data-state="collapsed">
        <p>Content for section 3.</p>
      </div>
    </div>
  `,
};

// Without a slotted invoker, a default `craft-button` is rendered from the
// `label` attribute.
export const DefaultInvoker: Story = {
  render: () => html`
    <craft-disclosure label="Advanced settings">
      <div slot="content" data-testid="target">This will toggle</div>
    </craft-disclosure>
  `,
  play: async ({canvas, userEvent}) => {
    const button = canvas
      .getByText('Advanced settings')
      .closest('craft-button') as HTMLElement;
    await expect(button).toHaveAttribute('aria-expanded', 'false');
    await userEvent.click(button);
    await expect(button).toHaveAttribute('aria-expanded', 'true');
  },
};
