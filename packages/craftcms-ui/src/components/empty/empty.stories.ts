import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html, nothing} from 'lit';

import type CraftEmpty from './empty.js';
import './empty.js';
import '../button/button.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Empty',
  component: 'craft-empty',
  args: {
    label: 'Nothing yet.',
    icon: 'magnifying-glass',
  },
  argTypes: {
    label: {
      control: {type: 'text'},
    },
    icon: {
      control: {type: 'text'},
      description: 'Icon name rendered above the label',
    },
  },
  render: ({label, icon}) => {
    return html`
      <craft-empty label="${label}" icon="${icon || nothing}"></craft-empty>
    `;
  },
} satisfies Meta<CraftEmpty>;

export default meta;
type Story = StoryObj<CraftEmpty>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const LabelOnly: Story = {
  args: {
    icon: '',
  },
};

export const WithAction: Story = {
  render: ({label, icon}) => {
    return html`
      <craft-empty label="${label}" icon="${icon || nothing}">
        <craft-button icon="plus">New entry</craft-button>
      </craft-empty>
    `;
  },
};

export const CustomContent: Story = {
  render: ({icon}) => {
    return html`
      <craft-empty icon="${icon || nothing}">
        <div slot="content">
          <p style="font-size: 1.25em; margin-block-end: 0">
            No entries match your search.
          </p>
          <p>Try a different keyword, or clear the filters.</p>
        </div>
        <craft-button appearance="outline">Clear filters</craft-button>
      </craft-empty>
    `;
  },
};

export const CustomGraphic: Story = {
  render: ({label}) => {
    return html`
      <craft-empty label="${label}">
        <svg
          slot="graphic"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          width="48"
          height="48"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"
          />
        </svg>
      </craft-empty>
    `;
  },
};
