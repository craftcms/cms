import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-handle.js';
import '../input/input.js';
import type CraftInputHandle from './input-handle.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input-handle.ts` surfaces it here without touching this file.
 *
 * The template is written out rather than generated: the value is Lion's
 * `modelValue`, a property rather than an attribute, and the label is a slot.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftInputHandle>('craft-input-handle');

type CraftInputHandleArgs = CraftInputHandle & typeof args;

/** The shell every story shares, so each one only supplies its args. */
const field = (args: Record<string, unknown>) => html`
  <craft-input-handle
    autocorrect="${(args.autocorrect as string) ?? 'off'}"
    autocapitalize="${(args.autocapitalize as string) ?? 'off'}"
    maxlength="${(args.maxlength as number) ?? ''}"
    ?disabled="${args.disabled}"
    .modelValue="${args.value ?? ''}"
  >
    <label slot="label">${args['label-slot']}</label>
    ${args['help-text-slot']
      ? html`<span slot="help-text">${args['help-text-slot']}</span>`
      : ''}
  </craft-input-handle>
`;

const meta = {
  title: 'Form Controls/Text Controls/Input Handle',
  component: 'craft-input-handle',
  args: {
    ...args,
    'label-slot': 'Handle',
    'help-text-slot': 'Used in templates.',
    value: 'entryType',
  },
  argTypes,
  render: (args) => field(args),
} satisfies Meta<CraftInputHandleArgs>;

export default meta;
type Story = StoryObj<CraftInputHandleArgs>;

/** A monospace field with the browser's text conveniences turned off. */
export const Default: Story = {};

/** Handles are read character by character, so the value is monospaced. */
export const ComparedToInput: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      <craft-input .modelValue="${'entryType'}">
        <label slot="label">craft-input</label>
      </craft-input>
      <craft-input-handle .modelValue="${'entryType'}">
        <label slot="label">craft-input-handle</label>
      </craft-input-handle>
    </div>
  `,
};

/**
 * `autocorrect` and `autocapitalize` are off by default here, and serialise as
 * `on`/`off` rather than as bare boolean attributes.
 */
export const AutocorrectOn: Story = {
  args: {
    autocorrect: 'on',
    autocapitalize: 'sentences',
    'help-text-slot':
      'autocorrect="on" — the browser may correct what is typed.',
  },
};

/** Everything from `craft-input` still applies, including the width rules. */
export const WithMaxLength: Story = {
  args: {maxlength: 12, 'help-text-slot': ''},
};

export const Disabled: Story = {
  args: {disabled: true, 'help-text-slot': ''},
};
