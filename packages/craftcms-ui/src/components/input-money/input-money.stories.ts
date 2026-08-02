import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import './input-money.js';

const meta = {
  title: 'Controls/Input Money',
  component: 'craft-input-money',
} satisfies Meta;

export default meta;
type Story = StoryObj;

export const DecimalAmount: Story = {
  render: () => html`
    <craft-input-money
      label="Price"
      name="price"
      currency="USD"
      .modelValue=${'12.99'}
    ></craft-input-money>
  `,
};

export const MinorUnits: Story = {
  render: () => html`
    <craft-input-money
      label="Price in cents"
      name="price"
      currency="EUR"
      currency-label="€"
      decimal-separator=","
      group-separator="."
      minor-units
      .modelValue=${1299}
    ></craft-input-money>
  `,
};
