import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import type {KeyedTableColumn, KeyedTableRow} from './keyed-table.js';
import './keyed-table.js';

const columns: KeyedTableColumn[] = [
  {key: 'uri', label: 'URI format', placeholder: 'news/{slug}', code: true},
  {key: 'template', label: 'Template', placeholder: 'news/_entry'},
];

const rows: KeyedTableRow[] = [
  {key: 'english', label: 'English'},
  {key: 'french', label: 'French'},
];

const meta = {
  title: 'Controls/Keyed Table',
  component: 'craft-keyed-table',
  args: {
    readOnly: false,
  },
  render: ({readOnly}) => html`
    <craft-keyed-table
      name="siteSettings"
      .columns=${columns}
      .rows=${rows}
      .modelValue=${{
        english: {uri: 'news/{slug}', template: 'news/_entry'},
        french: {uri: 'actualites/{slug}', template: 'news/_entry'},
      }}
      ?readonly=${readOnly}
    ></craft-keyed-table>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {};
