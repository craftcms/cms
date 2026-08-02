import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import type {ObjectSelectOption} from './object-select.js';
import './object-select.js';

const entries = [
  {uid: 'home', name: 'Home'},
  {uid: 'news', name: 'News'},
  {uid: 'contact', name: 'Contact'},
];

const options: ObjectSelectOption[] = entries.map((entry) => ({
  key: entry.uid,
  label: entry.name,
  value: entry,
}));

const meta = {
  title: 'Controls/Object Select',
  component: 'craft-object-select',
  args: {
    readOnly: false,
  },
  render: ({readOnly}) => html`
    <craft-object-select
      name="featuredEntries"
      identity-key="uid"
      .options=${options}
      .modelValue=${entries.slice(0, 2)}
      ?readonly=${readOnly}
    ></craft-object-select>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {};
