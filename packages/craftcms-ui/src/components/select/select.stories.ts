import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './select.js';
import type CraftSelect from './select.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `select.ts` surfaces it here without touching this file.
 *
 * The template is written out rather than generated: Lion expects the real
 * `<select>` in its `input` slot, and a generated slot wraps its content in a
 * `<span>`, which Lion then adopts as the control.
 */
const {args, argTypes} = getStorybookHelpers<CraftSelect>('craft-select');

type SelectArgs = CraftSelect & typeof args;

const meta = {
  title: 'Form Controls/Select Controls/Select',
  component: 'craft-select',
  args: {...args, name: 'language', 'label-slot': 'Language'},
  argTypes,
  render: (args) => html`
    <craft-select
      name="${args.name ?? ''}"
      ?small="${args.small}"
      ?disabled="${args.disabled}"
    >
      <label slot="label">${args['label-slot']}</label>
      ${args['help-text-slot']
        ? html`<span slot="help-text">${args['help-text-slot']}</span>`
        : ''}
      <select slot="input">
        <option value="">Select a language</option>
        <option value="fr-FR">French</option>
        <option value="en-US" selected>English</option>
        <option value="de-DE">German</option>
      </select>
    </craft-select>
  `,
} satisfies Meta<SelectArgs>;

export default meta;
type Story = StoryObj<SelectArgs>;

/** The native `<select>` goes in the `input` slot; the component draws the rest. */
export const Default: Story = {};

/** `small` steps the control down for a dense row. */
export const Small: Story = {
  args: {small: true},
};

export const WithHelpText: Story = {
  args: {'help-text-slot': 'Used for date and number formatting.'},
};

export const Disabled: Story = {
  args: {disabled: true},
};
