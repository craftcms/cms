import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-password.js';
import type CraftInputPassword from './input-password.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input-password.ts` surfaces it here without touching this file.
 *
 * The template is written out rather than generated: the value is Lion's
 * `modelValue`, a property rather than an attribute, and the label is a slot.
 */
const {args, argTypes} = getStorybookHelpers<CraftInputPassword>(
  'craft-input-password'
);

type CraftInputPasswordArgs = CraftInputPassword & typeof args;

/** The shell every story shares, so each one only supplies its args. */
const field = (args: Record<string, unknown>) => html`
  <craft-input-password
    ?disabled="${args.disabled}"
    .modelValue="${args.value ?? ''}"
  >
    <label slot="label">${args['label-slot']}</label>
    ${args['help-text-slot']
      ? html`<span slot="help-text">${args['help-text-slot']}</span>`
      : ''}
  </craft-input-password>
`;

const meta = {
  title: 'Form Controls/Text Controls/Input Password',
  component: 'craft-input-password',
  args: {
    ...args,
    'label-slot': 'Password',
    'help-text-slot': 'Use the eye button to show or hide what you typed.',
    value: 'SuperSecurePassword123',
  },
  argTypes,
  render: (args) => field(args),
} satisfies Meta<CraftInputPasswordArgs>;

export default meta;
type Story = StoryObj<CraftInputPasswordArgs>;

/** The value is masked until the reveal button in the suffix is pressed. */
export const Default: Story = {};

/** An empty field, as it arrives on a sign-in form. */
export const Empty: Story = {
  args: {value: '', 'help-text-slot': ''},
};

export const Disabled: Story = {
  args: {disabled: true, 'help-text-slot': ''},
};
