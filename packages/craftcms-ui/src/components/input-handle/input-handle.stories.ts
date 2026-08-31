import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './input-handle.js';
import '../input/input.js';

/**
 * Lion's `label` and `value` are not in the custom elements manifest, so they
 * cannot be driven through the storybook helpers' `template()`. These stories
 * render the element themselves and read the args they need.
 */
const meta = {
  title: 'Controls/Input Handle',
  component: 'craft-input-handle',
  args: {
    label: 'Handle',
    value: 'entryType',
    helpText: 'Used in templates.',
  },
  render: ({label, value, helpText}) => html`
    <craft-input-handle
      label="${label}"
      help-text="${helpText}"
      .modelValue="${value}"
    ></craft-input-handle>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

/** A monospace field with the browser's text conveniences turned off. */
export const Default: Story = {};

/** Handles are read character by character, so the value is monospaced. */
export const ComparedToInput: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      <craft-input
        label="craft-input"
        .modelValue="${'entryType'}"
      ></craft-input>
      <craft-input-handle
        label="craft-input-handle"
        .modelValue="${'entryType'}"
      ></craft-input-handle>
    </div>
  `,
};

/**
 * `autocorrect` and `autocapitalize` are off by default here, and serialise as
 * `on`/`off` rather than as bare boolean attributes.
 */
export const AutocorrectOn: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-input-handle
      label="Handle"
      help-text='autocorrect="on" — the browser may correct what is typed.'
      autocorrect="on"
      autocapitalize="sentences"
      .modelValue="${'entryType'}"
    ></craft-input-handle>
  `,
};

/** Everything from `craft-input` still applies, including the width rules. */
export const WithMaxLength: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-input-handle
      label="Handle"
      maxlength="12"
      .modelValue="${'entryType'}"
    ></craft-input-handle>
  `,
};

export const Disabled: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-input-handle
      label="Handle"
      disabled
      .modelValue="${'entryType'}"
    ></craft-input-handle>
  `,
};
