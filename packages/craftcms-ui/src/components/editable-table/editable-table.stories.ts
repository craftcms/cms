import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import type {EditableTableColumn} from './editable-table.js';
import './editable-table.js';

const columns: EditableTableColumn[] = [
  {key: 'heading', label: 'Heading', type: 'text'},
  {key: 'enabled', label: 'Enabled', type: 'lightswitch'},
  {
    key: 'alignment',
    label: 'Alignment',
    type: 'select',
    options: [
      {label: 'Left', value: 'left'},
      {label: 'Center', value: 'center'},
      {label: 'Right', value: 'right'},
    ],
  },
];

const meta = {
  title: 'Controls/Editable Table',
  component: 'craft-editable-table',
  args: {
    readOnly: false,
  },
  render: ({readOnly}) => html`
    <craft-editable-table
      name="rows"
      .columns=${columns}
      .modelValue=${[
        {heading: 'Introduction', enabled: true, alignment: 'left'},
        {heading: 'Summary', enabled: false, alignment: 'center'},
      ]}
      ?readonly=${readOnly}
    ></craft-editable-table>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {};
