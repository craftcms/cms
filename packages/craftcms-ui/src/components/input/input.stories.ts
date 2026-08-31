import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input.js';
import '../icon/icon.js';
import type CraftInput from './input.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftInput>('craft-input');

type InputArgs = CraftInput & typeof args;

const meta = {
  title: 'Controls/Input',
  component: 'craft-input',
  args: {
    ...args,
    // Lion's `label` and `help-text` are not in the manifest, so the helpers
    // would drop them from `template()`. They are slots too, and slot args do
    // reach the element.
    'label-slot': 'Craft Input',
    'help-text-slot': 'This is some instructions text',
  },
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<InputArgs>;

export default meta;
type Story = StoryObj<InputArgs>;

/** `label` and `help-text` come from Lion, as attributes or slots. */
export const Default: Story = {};

/**
 * A `maxlength` both caps the value and shrinks the control to the expected
 * character width.
 */
export const WithMaxLength: Story = {
  args: {maxlength: 5, 'label-slot': 'Port'},
};

/** `width="full"` spans the column even when a `maxlength` is set. */
export const FullWidthOverride: Story = {
  args: {maxlength: 6, width: 'full', 'label-slot': 'Port'},
};

/** `width="auto"` shrinks the control without requiring a `maxlength`. */
export const AutoWidth: Story = {
  args: {width: 'auto', 'label-slot': 'Handle'},
};

/** The `prefix` slot sits inside the control, before the input. */
export const WithPrefix: Story = {
  args: {
    'label-slot': 'Search',
    'prefix-slot': '<craft-icon name="magnifying-glass"></craft-icon>',
  },
};

/** The `suffix` slot sits inside the control, after the input. */
export const WithSuffix: Story = {
  args: {
    'label-slot': 'Search',
    'suffix-slot': '<craft-icon name="magnifying-glass"></craft-icon>',
  },
};

/** `monospace` renders the value in a monospace font. */
export const Monospace: Story = {
  args: {monospace: true, 'label-slot': 'Handle'},
};

/** `center` centre-aligns the value. */
export const Centered: Story = {
  args: {center: true, maxlength: 4, 'label-slot': 'Year'},
};

/** `size` steps the control's height. */
export const Sizes: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      ${(['small', 'medium', 'large'] as const).map(
        (size) =>
          html`<craft-input
            label='size="${size}"'
            size="${size}"
          ></craft-input>`
      )}
    </div>
  `,
};

/**
 * `type` accepts any native input type. The date and time types have their own
 * components — [Input Date](?path=/docs/controls-input-date--docs) and
 * [Input Time](?path=/docs/controls-input-time--docs) — which add behaviour on
 * top.
 */
export const Types: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      ${(['text', 'email', 'url', 'number', 'password'] as const).map(
        (type) =>
          html`<craft-input
            label='type="${type}"'
            type="${type}"
          ></craft-input>`
      )}
    </div>
  `,
};
