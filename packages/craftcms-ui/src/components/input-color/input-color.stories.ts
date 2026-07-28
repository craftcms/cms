import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './input-color.js';

const meta = {
  title: 'Controls/Input Color',
  component: 'craft-input-color',
  args: {
    label: 'Fill Color',
    value: '7ab55c',
  },
  render: function ({label, value}) {
    return html`<craft-input-color
      label="${label}"
      .modelValue="${value}"
    ></craft-input-color>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {
  args: {},
};

export const Shorthand: Story = {
  args: {
    value: 'abc',
  },
};

export const Invalid: Story = {
  args: {
    value: 'not-a-color',
  },
};

export const Disabled: Story = {
  render: () =>
    html`<craft-input-color
      label="Fill Color"
      .modelValue="${'7ab55c'}"
      disabled
    ></craft-input-color>`,
};

export const WithPresets: Story = {
  render: () => html`
    <craft-input-color
      label="Fill Color"
      .modelValue="${'7ab55c'}"
      .presets="${['#ffffff', '000000', '#7ab55c', 'e5422b']}"
    ></craft-input-color>
  `,
};

export const WithError: Story = {
  render: () => html`
    <craft-input-color
      label="Fill Color"
      .modelValue="${'not-a-color'}"
      has-feedback-for="error"
    >
      <div slot="feedback">
        <ul class="error-list">
          <li>Enter a valid hex color.</li>
        </ul>
      </div>
    </craft-input-color>
  `,
};
