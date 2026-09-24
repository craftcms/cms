import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './empty.js';
import type CraftEmpty from './empty.js';
import '../button/button.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `empty.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftEmpty>('craft-empty');

type EmptyArgs = CraftEmpty & typeof args;

const meta = {
  title: 'Components/Empty',
  component: 'craft-empty',
  args: {...args, label: 'Nothing yet.', icon: 'magnifying-glass'},
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<EmptyArgs>;

export default meta;
type Story = StoryObj<EmptyArgs>;

/** An icon above a label, which is the whole component. */
export const Default: Story = {};

/** Drop the `icon` for a quieter state. */
export const LabelOnly: Story = {
  args: {icon: ''},
};

/** The default slot holds whatever gets the reader out of the empty state. */
export const WithAction: Story = {
  args: {
    'default-slot': '<craft-button icon="plus">New entry</craft-button>',
  },
};

/** The `content` slot replaces the label when it needs more than one line. */
export const CustomContent: Story = {
  args: {
    label: '',
    'content-slot': `
      <p style="font-size: 1.25em; margin-block-end: 0">
        No entries match your search.
      </p>
      <p>Try a different keyword, or clear the filters.</p>
    `,
    'default-slot':
      '<craft-button appearance="outline">Clear filters</craft-button>',
  },
};

/** The `graphic` slot replaces the icon with artwork of your own. */
export const CustomGraphic: Story = {
  args: {
    icon: '',
    'graphic-slot': `
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
        stroke-width="1.5" width="48" height="48" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
      </svg>
    `,
  },
};
