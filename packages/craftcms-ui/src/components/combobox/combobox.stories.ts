import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import {html} from 'lit';
import type {ComboboxItem} from './combobox.js';
import './combobox.js';
import type CraftCombobox from './combobox.js';

const countries: ComboboxItem[] = [
  {label: 'United States', value: 'us'},
  {label: 'Canada', value: 'ca'},
  {label: 'Mexico', value: 'mx'},
  {label: 'United Kingdom', value: 'uk'},
  {label: 'France', value: 'fr'},
  {label: 'Germany', value: 'de'},
];

const statuses: ComboboxItem[] = [
  {label: 'Online', value: 'true', data: {indicator: {variant: 'success'}}},
  {label: 'Offline', value: 'false', data: {indicator: {variant: 'danger'}}},
];

const grouped: ComboboxItem[] = [
  {
    type: 'optgroup',
    label: 'North America',
    options: [
      {label: 'United States', value: 'us'},
      {label: 'Canada', value: 'ca'},
      {label: 'Mexico', value: 'mx'},
    ],
  },
  {
    type: 'optgroup',
    label: 'Europe',
    options: [
      {label: 'United Kingdom', value: 'uk'},
      {label: 'France', value: 'fr'},
      {label: 'Germany', value: 'de'},
    ],
  },
];

// A large, deterministic option list to exercise filtering + capping.
const manyOptions: ComboboxItem[] = Array.from({length: 400}, (_, i) => ({
  label: `Option ${String(i).padStart(3, '0')}`,
  value: `opt-${i}`,
  data: {keywords: `item number ${i}`},
}));

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `combobox.ts` surfaces it here without touching this file.
 */
const {args, argTypes} = getStorybookHelpers<CraftCombobox>('craft-combobox');

type CraftComboboxArgs = CraftCombobox & typeof args;

const meta = {
  title: 'Form Controls/Select Controls/Combobox',
  component: 'craft-combobox',
  args,
  argTypes,
} satisfies Meta<CraftComboboxArgs>;

export default meta;
type Story = StoryObj<CraftComboboxArgs>;

export const Default: Story = {
  render: () =>
    html`<craft-combobox
      label="Country"
      name="country"
      .options=${countries}
    ></craft-combobox>`,
};

export const RichOptions: Story = {
  render: () =>
    html`<craft-combobox
      label="System Status"
      name="status"
      show-all-on-empty
      .options=${statuses}
    ></craft-combobox>`,
};

export const Grouped: Story = {
  render: () =>
    html`<craft-combobox
      label="Country"
      name="country"
      .options=${grouped}
    ></craft-combobox>`,
};

export const Clearable: Story = {
  render: () =>
    html`<craft-combobox
      label="Country"
      name="country"
      clearable
      .options=${countries}
    ></craft-combobox>`,
};

/**
 * 400 options — only the first `limit` (150 by default) are ever rendered to
 * the DOM. Type to filter; the footer notes when results are capped.
 */
export const LargeList: Story = {
  render: () =>
    html`<craft-combobox
      label="Big list"
      name="big"
      show-all-on-empty
      .options=${manyOptions}
    ></craft-combobox>`,
};
