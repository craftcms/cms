import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import './element-condition.js';

const meta = {
  title: 'Controls/Element Condition',
  component: 'craft-element-condition',
  args: {
    readOnly: false,
  },
  render: ({readOnly}) => html`
    <form>
      <craft-element-condition
        name="condition"
        condition-class="EntryCondition"
        ?readonly=${readOnly}
      >
        <input type="hidden" name="condition[class]" value="EntryCondition" />
        <label>
          Title
          <select name="condition[conditionRules][0][operator]">
            <option value="contains">contains</option>
            <option value="equals">equals</option>
          </select>
          <input name="condition[conditionRules][0][value]" value="News" />
        </label>
      </craft-element-condition>
    </form>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const ServerRendered: Story = {};
