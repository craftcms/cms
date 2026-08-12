import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect} from 'storybook/test';

import {html} from 'lit';

import '../tab/tab.js';
import './tabs.js';
import {tabsLayouts} from './tabs.js';

const meta = {
  title: 'Components/Tabs',
  component: 'craft-tabs',
  argTypes: {
    layout: {
      control: {type: 'inline-radio'},
      options: tabsLayouts,
      description: 'Which axis the tab strip runs along.',
    },
    selectedIndex: {
      name: 'selected-index',
      control: {type: 'number'},
      description: 'Index of the selected tab.',
    },
  },
  args: {
    layout: 'horizontal',
    selectedIndex: 0,
  },
  render: (args) => html`
    <craft-tabs layout="${args.layout}" selected-index="${args.selectedIndex}">
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

/*
 * These play functions are the real test bed for the Lion-driven behavior:
 * it bootstraps from a `slotchange`, which happy-dom never fires, so
 * tabs.test.ts can only cover the wrapper. See the note at the top of that
 * file.
 */

/**
 * Sends an arrow key to a tab. Lion navigates on `keyup`, and this dispatches
 * the event at the tab directly rather than going through `userEvent.keyboard`,
 * which routes to whatever the *browser* considers focused — unreliable here,
 * since the story runs in an iframe that may not hold system focus.
 */
function arrow(tab: HTMLElement, key: 'ArrowLeft' | 'ArrowRight') {
  tab.focus();
  tab.dispatchEvent(new KeyboardEvent('keydown', {key, bubbles: true}));
  tab.dispatchEvent(new KeyboardEvent('keyup', {key, bubbles: true}));
}

export const Default: Story = {
  play: async ({canvas, userEvent}) => {
    const tabs = canvas.getAllByRole('tab');

    await expect(tabs[0]).toHaveAttribute('aria-selected', 'true');
    await expect(tabs[1]).toHaveAttribute('aria-selected', 'false');

    await userEvent.click(tabs[2]!);
    await expect(tabs[2]).toHaveAttribute('aria-selected', 'true');
    await expect(tabs[0]).toHaveAttribute('aria-selected', 'false');

    // Roving tabindex: only the selected tab is in the tab order.
    await expect(tabs[2]).toHaveAttribute('tabindex', '0');
    await expect(tabs[0]).toHaveAttribute('tabindex', '-1');

    arrow(tabs[2]!, 'ArrowLeft');
    await expect(tabs[1]).toHaveAttribute('aria-selected', 'true');
  },
};

export const Vertical: Story = {
  args: {layout: 'vertical'},
  play: async ({canvasElement}) => {
    // The tablist lives in the shadow root, out of the light-DOM canvas.
    const tabList = canvasElement
      .querySelector('craft-tabs')!
      .shadowRoot!.querySelector('[part="tab-group"]')!;

    await expect(tabList).toHaveAttribute('aria-orientation', 'vertical');
  },
};

/** The second tab starts selected. */
export const SelectedIndex: Story = {
  args: {selectedIndex: 1},
  play: async ({canvas}) => {
    await expect(canvas.getAllByRole('tab')[1]).toHaveAttribute(
      'aria-selected',
      'true'
    );
  },
};

/**
 * Disabled tabs can't be clicked and are skipped by arrow-key navigation. Lion
 * also moves the initial selection off a disabled first tab.
 */
export const Disabled: Story = {
  render: () => html`
    <craft-tabs>
      <craft-tab slot="tab">Enabled</craft-tab>
      <div slot="panel"><p>This tab can be selected.</p></div>
      <craft-tab slot="tab" disabled>Disabled</craft-tab>
      <div slot="panel"><p>You shouldn't be able to get here.</p></div>
      <craft-tab slot="tab">Also enabled</craft-tab>
      <div slot="panel"><p>This tab can be selected too.</p></div>
    </craft-tabs>
  `,
  play: async ({canvas}) => {
    const tabs = canvas.getAllByRole('tab');

    // A disabled tab is out of hit-testing entirely, so a click can't reach
    // the handler <craft-tabs> binds to every tab. (userEvent refuses to click
    // it for that same reason, which is why this asserts the style.)
    await expect(getComputedStyle(tabs[1]!).pointerEvents).toBe('none');
    await expect(tabs[1]).toHaveAttribute('aria-selected', 'false');

    // Arrow keys hop over the disabled tab in the middle.
    arrow(tabs[0]!, 'ArrowRight');
    await expect(tabs[2]).toHaveAttribute('aria-selected', 'true');
  },
};

/**
 * When the panels can't be slotted next to their tabs — a server-rendered
 * field layout puts the tab bar in the page header and the sections inside a
 * pane — each tab names its panel by `id` instead, and the strip drives those
 * panels where they stand.
 */
export const ExternalPanels: Story = {
  render: () => html`
    <style>
      .hidden {
        display: none;
      }
    </style>

    <craft-tabs>
      <craft-tab slot="tab" controls="external-content">Content</craft-tab>
      <craft-tab slot="tab" controls="external-settings">Settings</craft-tab>
    </craft-tabs>

    <p>Anything at all can sit between the strip and its panels.</p>

    <section id="external-content"><p>The content panel.</p></section>
    <section id="external-settings" class="hidden">
      <p>The settings panel.</p>
    </section>
  `,
  play: async ({canvas, canvasElement, userEvent}) => {
    const tabs = canvas.getAllByRole('tab');
    const content = canvasElement.querySelector('#external-content')!;
    const settings = canvasElement.querySelector('#external-settings')!;

    await expect(tabs[0]).toHaveAttribute('aria-controls', 'external-content');
    await expect(content).toHaveAttribute('role', 'tabpanel');
    await expect(content).toHaveAttribute('aria-labelledby', tabs[0]!.id);

    await userEvent.click(tabs[1]!);
    await expect(content).toHaveClass('hidden');
    await expect(settings).not.toHaveClass('hidden');
  },
};
