import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-copy.js';
import type CraftInputCopy from './input-copy.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input-copy.ts` surfaces it here without touching this file.
 *
 * The template is written out rather than generated: the value is Lion's
 * `modelValue`, a property rather than an attribute, and the label is a slot.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftInputCopy>('craft-input-copy');

type CraftInputCopyArgs = CraftInputCopy & typeof args;

/** The shell every story shares, so each one only supplies its args. */
const field = (args: Record<string, unknown>) => html`
  <craft-input-copy
    copy-value="${(args['copy-value'] as string) ?? ''}"
    ?disabled="${args.disabled}"
    .modelValue="${args.value ?? ''}"
  >
    <label slot="label">${args['label-slot']}</label>
    ${args['help-text-slot']
      ? html`<span slot="help-text">${args['help-text-slot']}</span>`
      : ''}
  </craft-input-copy>
`;

const meta = {
  title: 'Form Controls/Text Controls/Input Copy',
  component: 'craft-input-copy',
  args: {
    ...args,
    'label-slot': 'Site URL',
    'help-text-slot': 'Click the copy button to copy this value.',
    value: 'https://craftcms.com',
  },
  argTypes,
  render: (args) => field(args),
} satisfies Meta<CraftInputCopyArgs>;

export default meta;
type Story = StoryObj<CraftInputCopyArgs>;

/** A read-only value with a copy button in the suffix. */
export const Default: Story = {};

/**
 * `copy-value` sends something other than what is shown — a full token behind
 * a masked one, say.
 */
export const WithSeparateCopyValue: Story = {
  args: {
    'label-slot': 'API Token',
    'help-text-slot': '',
    value: 'sk-••••••••••••••••••••••••1234',
    'copy-value': 'sk-abcdefghijklmnopqrstuvwxyz1234',
  },
};

export const Disabled: Story = {
  args: {disabled: true, 'help-text-slot': ''},
};
