import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import type {CheckboxSelectOption} from './checkbox-select.js';
import './checkbox-select.js';

const options: CheckboxSelectOption[] = [
  {label: 'All sections', value: '*'},
  {label: 'News', value: 'news', icon: 'newspaper'},
  {label: 'Events', value: 'events', icon: 'calendar'},
  {label: 'Private', value: 'private', disabled: true},
];

const meta = {
  title: 'Controls/Checkbox Select',
  component: 'craft-checkbox-select',
  args: {
    readOnly: false,
    sortable: true,
  },
  render: ({readOnly, sortable}) => html`
    <craft-checkbox-select
      id="section-select"
      name="sections"
      .options=${options}
      .modelValue=${['news', 'events']}
      .allOption=${'*'}
      ?readonly=${readOnly}
      ?sortable=${sortable}
    ></craft-checkbox-select>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {};
