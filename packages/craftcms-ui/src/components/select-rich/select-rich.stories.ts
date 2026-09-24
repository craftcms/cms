import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './select-rich.js';
import '../option/option.js';
import '../indicator/indicator.js';
import type CraftSelectRich from './select-rich.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `select-rich.ts` surfaces it here without touching this file.
 *
 * The options are written out rather than generated: Lion registers each
 * `craft-option` as a real child, and a generated slot would wrap them in a
 * `<span>` that Lion adopts as the control instead.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftSelectRich>('craft-select-rich');

type SelectRichArgs = CraftSelectRich & typeof args;

/** The shell every story shares, so each one only supplies its options. */
const select = (args: Record<string, unknown>, options: unknown) => html`
  <craft-select-rich
    name="${(args.name as string) ?? ''}"
    ?small="${args.small}"
    ?disabled="${args.disabled}"
  >
    <label slot="label">${args['label-slot']}</label>
    ${options}
  </craft-select-rich>
`;

const meta = {
  title: 'Form Controls/Select Controls/Select Rich',
  component: 'craft-select-rich',
  args: {...args, name: 'fruit', 'label-slot': 'Favorite Fruit'},
  argTypes,
  render: (args) =>
    select(
      args,
      html`
        <craft-option .choiceValue="${'apple'}">Apple</craft-option>
        <craft-option .choiceValue="${'banana'}">Banana</craft-option>
        <craft-option .choiceValue="${'cherry'}">Cherry</craft-option>
        <craft-option .choiceValue="${'grape'}">Grape</craft-option>
        <craft-option .choiceValue="${'mango'}">Mango</craft-option>
      `
    ),
} satisfies Meta<SelectRichArgs>;

export default meta;
type Story = StoryObj<SelectRichArgs>;

/** Options are `craft-option` children rather than native `<option>`s. */
export const Default: Story = {};

/** Which is what lets an option hold markup rather than only text. */
export const RichOptions: Story = {
  args: {name: 'status', 'label-slot': 'System Status'},
  render: (args) =>
    select(
      args,
      html`
        <craft-option .choiceValue="${'online'}">
          <div class="flex items-center gap-1">
            <craft-indicator fill="success"></craft-indicator
            ><span>Online</span>
          </div>
        </craft-option>
        <craft-option .choiceValue="${'maintenance'}">
          <div class="flex items-center gap-1">
            <craft-indicator fill="warning"></craft-indicator
            ><span>Maintenance</span>
          </div>
        </craft-option>
        <craft-option .choiceValue="${'offline'}">
          <div class="flex items-center gap-1">
            <craft-indicator fill="danger"></craft-indicator
            ><span>Offline</span>
          </div>
        </craft-option>
      `
    ),
};

/** `small` steps the control down for a dense row. */
export const Small: Story = {
  args: {small: true, name: 'size', 'label-slot': 'Size'},
  render: (args) =>
    select(
      args,
      html`
        <craft-option .choiceValue="${'sm'}">Small</craft-option>
        <craft-option .choiceValue="${'md'}">Medium</craft-option>
        <craft-option .choiceValue="${'lg'}">Large</craft-option>
      `
    ),
};

/** An option's `hint` sits after its label, for a count or a note. */
export const WithHints: Story = {
  args: {name: 'section', 'label-slot': 'Section'},
  render: (args) =>
    select(
      args,
      html`
        <craft-option .choiceValue="${'blog'}" hint="12 entries"
          >Blog</craft-option
        >
        <craft-option .choiceValue="${'news'}" hint="4 entries"
          >News</craft-option
        >
        <craft-option .choiceValue="${'docs'}" hint="89 entries"
          >Documentation</craft-option
        >
      `
    ),
};
