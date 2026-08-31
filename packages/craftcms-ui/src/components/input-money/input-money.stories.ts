import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './input-money.js';

const meta = {
  title: 'Form Controls/Text Controls/Input Money',
  component: 'craft-input-money',
  args: {
    label: 'Price',
    value: '1,234.56',
    currency: 'USD',
    locale: 'en-US',
    showCurrency: true,
    clearable: true,
    disabled: false,
  },
  render: function ({
    label,
    value,
    currency,
    locale,
    showCurrency,
    clearable,
    disabled,
  }) {
    return html`<craft-input-money
      label=${label}
      .modelValue=${value}
      currency=${currency}
      locale=${locale}
      .showCurrency=${showCurrency}
      .clearable=${clearable}
      ?disabled=${disabled}
    ></craft-input-money>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {};

export const LocalizedEuro: Story = {
  args: {
    value: '1.234,56',
    currency: 'EUR',
    locale: 'de-DE',
  },
};

export const JapaneseYen: Story = {
  args: {
    value: '123,456',
    currency: 'JPY',
    locale: 'ja-JP',
  },
};

export const WithoutAffordances: Story = {
  args: {
    showCurrency: false,
    clearable: false,
  },
};

export const Disabled: Story = {
  args: {
    disabled: true,
  },
};
