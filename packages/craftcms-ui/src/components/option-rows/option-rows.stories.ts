import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import './option-rows.js';

const meta = {
  title: 'Controls/Option Rows',
  component: 'craft-option-rows',
  args: {
    readOnly: false,
  },
  render: ({readOnly}) => html`
    <craft-option-rows
      name="options"
      .modelValue=${[
        {label: 'News', value: 'news', default: true},
        {label: 'Opinion', value: 'opinion', default: false},
      ]}
      ?readonly=${readOnly}
    ></craft-option-rows>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {};
