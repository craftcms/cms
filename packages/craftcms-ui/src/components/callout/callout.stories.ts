import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html, nothing} from 'lit';

import './callout.js';
import {appearances} from '@src/constants/appearances.js';
import {variants} from '@src/constants/variants.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Callout',
  component: 'craft-callout',
  args: {
    appearance: 'outline-fill',
    variant: 'default',
    flash: false,
    message: 'This is a callout message',
  },
  argTypes: {
    appearance: {
      control: {type: 'select'},
      options: appearances,
    },
    variant: {
      control: {type: 'select'},
      options: appearances,
    },
    message: {
      control: {type: 'text'},
    },
    flash: {
      control: {type: 'boolean'},
    },
  },
  render: ({appearance, variant, message}) => {
    return html`
      <craft-callout variant="${variant}" appearance="${appearance}"
        >${message}</craft-callout
      >
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const WithIcon: Story = {
  args: {
    variant: 'info',
  },
  render: ({appearance, variant, message}) => {
    return html`
      <craft-callout
        variant="${variant}"
        appearance="${appearance}"
        icon="circle-info"
      >
        ${message}
      </craft-callout>
    `;
  },
};

export const WithCustomIcon: Story = {
  args: {
    variant: 'info',
  },
  render: ({appearance, variant, message}) => {
    return html`
      <craft-callout variant="${variant}" appearance="${appearance}">
        <span slot="icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            width="1lh"
            height="1lh"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"
            />
          </svg>
        </span>

        ${message}
      </craft-callout>
    `;
  },
};

export const WithTitle: Story = {
  args: {
    variant: 'info',
  },
  render: ({appearance, variant, message}) => {
    return html`
      <craft-callout variant="${variant}" appearance="${appearance}">
        <craft-icon slot="icon" name="circle-info"></craft-icon>
        <span slot="title">This is a title</span>
        ${message}
      </craft-callout>
    `;
  },
};

export const KitchenSink: Story = {
  args: {
    variant: 'danger',
  },
  render: ({appearance, variant, message}) => {
    return html`
      <craft-callout variant="${variant}" appearance="${appearance}">
        <craft-icon slot="icon" name="triangle-exclamation"></craft-icon>
        <span slot="title">Unable to save entry.</span>

        <p>Please correct the errors and try again.</p>
        <ul class="list">
          <li><a href="#">Title</a> is required.</li>
          <li><a href="#">Slug</a> is required.</li>
          <li><a href="#">Feature Image</a> must have at least one item.</li>
        </ul>

        <craft-button
          type="button"
          slot="action"
          variant="outline"
          inherit
          size="small"
          >Action</craft-button
        >
      </craft-callout>
    `;
  },
};

export const Variants: Story = {
  args: {
    appearance: 'outline-fill',
  },
  render({appearance}) {
    return html`
      <div class="stack">
        ${variants.map((variant) => {
          return html`
            <craft-callout variant="${variant}" appearance="${appearance}"
              >${variant}</craft-callout
            >
          `;
        })}
      </div>
    `;
  },
};

/**
 * The default (no `padding` attribute) keeps the callout's asymmetric pair —
 * `sm` on the block axis, `md` on the inline one. Any value that is given
 * applies to both axes, the way a one-value CSS `padding` shorthand does.
 */
export const Padding: Story = {
  args: {
    variant: 'info',
  },
  argTypes: {
    padding: {
      control: {type: 'text'},
    },
  },
  render: ({appearance, variant, message}) => {
    return html`
      <div class="stack">
        ${[undefined, 'none', 'sm', 'md', 'lg', 'xl', '24', '2rem'].map(
          (padding) => html`
            <craft-callout
              variant="${variant}"
              appearance="${appearance}"
              padding="${padding ?? nothing}"
            >
              ${padding ? `padding="${padding}"` : 'no padding attribute'} —
              ${message}
            </craft-callout>
          `
        )}
      </div>
    `;
  },
};

export const Appearances: Story = {
  args: {},
  render: (args) => {
    return html`
      <div class="stack">
        ${appearances.map((appearance) => {
          return html`
            <craft-callout variant="info" appearance="${appearance}"
              >${appearance}</craft-callout
            >
          `;
        })}
      </div>
    `;
  },
};
