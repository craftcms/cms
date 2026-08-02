import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import '../tab/tab.js';
import './tabs.js';

function removeLastPanel(event: Event): void {
  const tabs = (
    event.currentTarget as HTMLElement
  ).parentElement?.querySelector('craft-tabs');
  const panels = tabs?.querySelectorAll<HTMLElement>('[slot="panel"]');
  panels?.[panels.length - 1]?.remove();
}

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

export const DynamicChildren: Story = {
  render: () => html`
    <div style="display: grid; gap: 16px;">
      <craft-tabs>
        <craft-tab slot="tab">General</craft-tab>
        <div slot="panel"><p>General settings</p></div>
        <craft-tab slot="tab">Content</craft-tab>
        <div slot="panel"><p>Content settings</p></div>
        <craft-tab slot="tab">Advanced</craft-tab>
        <div slot="panel"><p>Advanced settings</p></div>
      </craft-tabs>
      <button type="button" @click=${removeLastPanel}>
        Remove the last panel
      </button>
    </div>
  `,
};
