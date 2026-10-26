import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './checkbox-select.js';
import type CraftCheckboxSelect from './checkbox-select.js';
import type {CheckboxSelectOption} from './checkbox-select.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc.
 */
const {args, argTypes} = getStorybookHelpers<CraftCheckboxSelect>(
  'craft-checkbox-select'
);

type CheckboxSelectArgs = CraftCheckboxSelect & typeof args;

const columns: CheckboxSelectOption[] = [
  {value: 'title', label: 'Title', checked: true},
  {value: 'author', label: 'Author', checked: true},
  {value: 'postDate', label: 'Post Date'},
  {value: 'expiryDate', label: 'Expiry Date'},
  {value: 'slug', label: 'Slug'},
];

const meta = {
  title: 'Form Controls/Choice Controls/Checkbox Select',
  component: 'craft-checkbox-select',
  args: {...args, name: 'columns', label: 'Columns'},
  argTypes,
  render: (args) => html`
    <craft-checkbox-select
      name=${args.name ?? ''}
      label=${args.label ?? ''}
      presentation=${args.presentation ?? 'checkboxes'}
      ?sortable=${args.sortable}
      ?disabled=${args.disabled}
      .options=${columns}
    ></craft-checkbox-select>
  `,
} satisfies Meta<CheckboxSelectArgs>;

export default meta;
type Story = StoryObj<CheckboxSelectArgs>;

/** Every option is a checkbox, posted in display order. */
export const Default: Story = {};

/**
 * An “All” option above the others. In `single-value` mode it posts its own
 * value alone, so the stored setting stays “all of them”.
 */
export const AllOption: Story = {
  render: () => html`
    <craft-checkbox-select
      name="sources"
      label="Sources"
      all-label="All"
      .options=${columns.map(({checked: _checked, ...option}) => option)}
    ></craft-checkbox-select>
  `,
};

/** `sortable` adds a reorder button to each option, which can also be dragged. */
export const Sortable: Story = {
  args: {sortable: true},
};

/**
 * `add-menu` lists only the checked options, with the rest in an “Add item”
 * menu.
 */
export const AddMenu: Story = {
  args: {sortable: true, presentation: 'add-menu'},
};

/** Sortable options can have an “All” option too. */
export const SortableWithAll: Story = {
  render: () => html`
    <craft-checkbox-select
      name="sources"
      label="Sources"
      all-label="All"
      sortable
      .options=${columns}
    ></craft-checkbox-select>
  `,
};

/** Options can be added, changed, and removed after the element renders. */
export const RuntimeOptions: Story = {
  render: () => {
    let count = 0;
    const select = (event: Event) =>
      (event.target as HTMLElement)
        .closest('div')!
        .querySelector('craft-checkbox-select')!;

    return html`
      <div
        style="display: grid; gap: var(--c-spacing-md); justify-items: start"
      >
        <craft-checkbox-select
          name="columns"
          label="Columns"
          sortable
          presentation="add-menu"
          .options=${columns}
        ></craft-checkbox-select>
        <craft-button
          type="button"
          @click=${(event: Event) => {
            count++;
            select(event).addOption({
              value: `field${count}`,
              label: `Field ${count}`,
              checked: true,
            });
          }}
        >
          Add a field
        </craft-button>
      </div>
    `;
  },
};
