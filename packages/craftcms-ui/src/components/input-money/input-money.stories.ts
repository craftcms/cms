import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-money.js';
import type CraftInputMoney from './input-money.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input-money.ts` surfaces it here without touching this file.
 *
 * The template is written out rather than generated: the value is Lion's
 * `modelValue`, a property rather than an attribute, and the label is a slot.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftInputMoney>('craft-input-money');

type CraftInputMoneyArgs = CraftInputMoney & typeof args;

/** The shell every story shares, so each one only supplies its args. */
const field = (args: Record<string, unknown>) => html`
  <craft-input-money
    currency="${(args.currency as string) ?? 'USD'}"
    locale="${(args.locale as string) ?? 'en-US'}"
    show-currency="${String(args['show-currency'] ?? true)}"
    clearable="${String(args.clearable ?? true)}"
    ?disabled="${args.disabled}"
    .modelValue="${args.value ?? ''}"
  >
    <label slot="label">${args['label-slot']}</label>
    ${args['help-text-slot']
      ? html`<span slot="help-text">${args['help-text-slot']}</span>`
      : ''}
  </craft-input-money>
`;

const meta = {
  title: 'Form Controls/Text Controls/Input Money',
  component: 'craft-input-money',
  args: {
    ...args,
    'label-slot': 'Price',
    value: '1,234.56',
    currency: 'USD',
    locale: 'en-US',
  },
  argTypes,
  render: (args) => field(args),
} satisfies Meta<CraftInputMoneyArgs>;

export default meta;
type Story = StoryObj<CraftInputMoneyArgs>;

/** The mask is built from `locale` and `currency`. */
export const Default: Story = {};

/** A different locale changes the separators as well as the symbol. */
export const LocalizedEuro: Story = {
  args: {value: '1.234,56', currency: 'EUR', locale: 'de-DE'},
};

/** A currency with no minor units drops the decimals. */
export const JapaneseYen: Story = {
  args: {value: '123,456', currency: 'JPY', locale: 'ja-JP'},
};

/**
 * `show-currency` and `clearable` are on unless explicitly turned off, so they
 * take the literal string `"false"` rather than a bare boolean attribute.
 */
export const WithoutAffordances: Story = {
  args: {'show-currency': false, clearable: false},
};

export const Disabled: Story = {
  args: {disabled: true},
};
