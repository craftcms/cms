import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-search.js';
import '../button/button.js';
import '../icon/icon.js';
import type CraftInputSearch from './input-search.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc.
 *
 * The template is written out rather than generated: the value is Lion's
 * `modelValue`, a property rather than an attribute.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftInputSearch>('craft-input-search');

type CraftInputSearchArgs = CraftInputSearch & typeof args;

const meta = {
  title: 'Form Controls/Text Controls/Input Search',
  component: 'craft-input-search',
  args: {...args, value: ''},
  argTypes,
  render: (args) => html`
    <craft-input-search
      ?disabled="${args.disabled}"
      .modelValue="${args.value ?? ''}"
    ></craft-input-search>
  `,
} satisfies Meta<CraftInputSearchArgs>;

export default meta;
type Story = StoryObj<CraftInputSearchArgs>;

/** Labeled and placeholdered "Search", with the search icon as its visible label. */
export const Default: Story = {};

/** The clear button appears once there's something to clear. */
export const WithValue: Story = {
  args: {value: 'Annual report'},
};

/** A label that says what's being searched replaces the default one. */
export const CustomLabel: Story = {
  render: () => html`
    <craft-input-search
      label="Search entries"
      placeholder="Search entries"
    ></craft-input-search>
  `,
};

/** Other suffix content, such as a filter button or a spinner, follows the clear button. */
export const WithSuffix: Story = {
  render: () => html`
    <craft-input-search .modelValue="${'Annual report'}">
      <craft-button
        slot="suffix"
        type="button"
        icon
        size="small"
        variant="plain"
      >
        <craft-icon name="filter" label="Filter results"></craft-icon>
      </craft-button>
    </craft-input-search>
  `,
};

export const Disabled: Story = {
  args: {disabled: true, value: 'Annual report'},
};
