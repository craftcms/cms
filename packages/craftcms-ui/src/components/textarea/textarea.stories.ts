import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './textarea.js';
import type CraftTextarea from './textarea.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `textarea.ts` surfaces it here without touching this file.
 *
 * Lion's own `label` and `help-text` are slots here, so they are set through
 * the `label-slot` and `help-text-slot` args rather than as attributes.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftTextarea>('craft-textarea');

type TextareaArgs = CraftTextarea & typeof args;

const meta = {
  title: 'Form Controls/Text Controls/Textarea',
  component: 'craft-textarea',
  args: {
    ...args,
    'label-slot': 'Description',
    'help-text-slot': 'Shown beneath the entry title in listings.',
  },
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<TextareaArgs>;

export default meta;
type Story = StoryObj<TextareaArgs>;

/** A multi-line field with the same label and help text as the other controls. */
export const Default: Story = {};

/** `monospace` suits anything read character by character. */
export const Monospace: Story = {
  args: {monospace: true, 'label-slot': 'Redirect rules'},
};

export const Disabled: Story = {
  args: {disabled: true},
};

export const Readonly: Story = {
  args: {readonly: true},
};
