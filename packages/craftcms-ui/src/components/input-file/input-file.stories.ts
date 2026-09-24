import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-file.js';
import type CraftInputFile from './input-file.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input-file.ts` surfaces it here without touching this file.
 *
 * `accept` and `multiple` come from Lion's file input rather than from this
 * component, so they are written into the template rather than generated.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftInputFile>('craft-input-file');

type InputFileArgs = CraftInputFile & typeof args;

/** The shell every story shares, so each one only supplies its args. */
const field = (args: Record<string, unknown>) => html`
  <craft-input-file
    accept="${(args.accept as string) ?? ''}"
    ?multiple="${args.multiple}"
    ?disabled="${args.disabled}"
  >
    <label slot="label">${args['label-slot']}</label>
    ${args['help-text-slot']
      ? html`<span slot="help-text">${args['help-text-slot']}</span>`
      : ''}
  </craft-input-file>
`;

const meta = {
  title: 'Form Controls/Input File',
  component: 'craft-input-file',
  args: {
    ...args,
    'label-slot': 'Upload file',
    'help-text-slot': 'Select a file to upload.',
  },
  argTypes,
  render: (args) => field(args),
} satisfies Meta<InputFileArgs>;

export default meta;
type Story = StoryObj<InputFileArgs>;

/** A button that opens the file dialog, and the list of what was chosen. */
export const Default: Story = {};

/** `multiple` lets the dialog return more than one file. */
export const Multiple: Story = {
  args: {
    multiple: true,
    'label-slot': 'Upload files',
    'help-text-slot': 'Select one or more files.',
  },
};

/** `accept` filters what the dialog offers, as on a native file input. */
export const WithAccept: Story = {
  args: {
    accept: 'image/*',
    'label-slot': 'Upload image',
    'help-text-slot': 'Images only.',
  },
};

export const Disabled: Story = {
  args: {disabled: true},
};
