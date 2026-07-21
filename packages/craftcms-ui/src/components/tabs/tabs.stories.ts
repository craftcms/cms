import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import '../tab/tab.js';
import './tabs.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Tabs',
  component: 'craft-tabs',
  argTypes: {},
  render: (args) => html`
    <craft-tabs>
      <craft-tab slot="tab">Tab One</craft-tab>
      <div slot="panel">
        <p>Some content for the first tab</p>
      </div>
      <craft-tab slot="tab">Tab Two</craft-tab>
      <div slot="panel">
        <p>Some content for the second tab</p>
      </div>
      <craft-tab slot="tab">Tab Three</craft-tab>
      <div slot="panel">
        <p>Some content for the third tab</p>
      </div>
    </craft-tabs>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
