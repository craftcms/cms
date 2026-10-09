import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './checkbox.js';
import type CraftCheckbox from './checkbox.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `checkbox.ts` surfaces it here without touching this file.
 *
 * Lion's own `label` and `help-text` are slots here, so they are set through
 * the `label-slot` and `help-text-slot` args rather than as attributes.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftCheckbox>('craft-checkbox');

type CheckboxArgs = CraftCheckbox & typeof args;

const meta = {
  title: 'Form Controls/Choice Controls/Checkbox',
  component: 'craft-checkbox',
  args: {
    ...args,
    'label-slot': 'Enable indexing',
    'help-text-slot': 'Search engines will be allowed to index this page.',
  },
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<CheckboxArgs>;

export default meta;
type Story = StoryObj<CheckboxArgs>;

/** A label, help text, and the box itself. */
export const Default: Story = {};

export const Checked: Story = {
  args: {checked: true},
};

/** `indeterminate` is the third state, for a box standing in for a group. */
export const Indeterminate: Story = {
  args: {indeterminate: true},
};

export const Disabled: Story = {
  args: {disabled: true},
};

/** `label-sr-only` keeps the name for assistive technology and hides it. */
export const NoVisibleLabel: Story = {
  args: {'label-sr-only': true, 'help-text-slot': ''},
  parameters: {
    a11y: {
      config: {
        // The label is still there and still wired up with `aria-labelledby`;
        // hiding it visually is the point of the story, and that is exactly
        // what this rule reports.
        rules: [{id: 'label-title-only', enabled: false}],
      },
    },
  },
};
