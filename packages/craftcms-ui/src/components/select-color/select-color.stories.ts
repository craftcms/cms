import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import './select-color.js';

const meta = {
  title: 'Controls/Select Color',
  component: 'craft-select-color',
  args: {
    label: 'Color',
    modelValue: 'red',
  },
  render: function ({label, modelValue}) {
    return html`<craft-select-color
      label="${label}"
      name="color"
      .modelValue="${modelValue}"
    ></craft-select-color>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

/**
 * A color is selected on load, so the invoker shows its swatch.
 */
export const Default: Story = {
  args: {},
};

/**
 * No color selected — the invoker shows the placeholder with no swatch.
 */
export const Empty: Story = {
  args: {
    modelValue: null,
  },
};

/**
 * Preselected blue — the blue swatch appears in the invoker.
 */
export const Preselected: Story = {
  args: {
    modelValue: 'blue',
  },
};

/**
 * With the transparent option enabled and preselected, the checkerboard swatch
 * appears in the invoker.
 */
export const AllowTransparent: Story = {
  render: () => html`
    <craft-select-color
      label="Color"
      name="color"
      allow-transparent
      .modelValue="${'__blank__'}"
    ></craft-select-color>
  `,
};

/**
 * The transparent option enabled but nothing selected, so the full list is
 * available from the invoker.
 */
export const AllowTransparentEmpty: Story = {
  render: () => html`
    <craft-select-color
      label="Color"
      name="color"
      allow-transparent
    ></craft-select-color>
  `,
};
