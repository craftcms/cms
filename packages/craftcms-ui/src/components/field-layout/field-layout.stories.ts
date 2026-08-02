import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import type {AvailableFieldLayoutElement} from './field-layout.js';
import './field-layout.js';

const availableElements: AvailableFieldLayoutElement[] = [
  {
    key: 'field:title',
    label: 'Title',
    value: {type: 'TitleField'},
    multiple: false,
  },
  {
    key: 'field:body',
    label: 'Body',
    value: {type: 'BodyField'},
    multiple: false,
  },
  {
    key: 'ui:line',
    label: 'Horizontal rule',
    value: {type: 'HorizontalRule'},
    multiple: true,
  },
];

const meta = {
  title: 'Controls/Field Layout',
  component: 'craft-field-layout',
  args: {
    readOnly: false,
  },
  render: ({readOnly}) => html`
    <craft-field-layout
      name="fieldLayout"
      .availableElements=${availableElements}
      .modelValue=${{
        tabs: [
          {
            uid: 'content',
            name: 'Content',
            elements: [
              {uid: 'title', type: 'TitleField'},
              {uid: 'body', type: 'BodyField'},
            ],
          },
          {uid: 'meta', name: 'Meta', elements: []},
        ],
      }}
      ?readonly=${readOnly}
    ></craft-field-layout>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {};
