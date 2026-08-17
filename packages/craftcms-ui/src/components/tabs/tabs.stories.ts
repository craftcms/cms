import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect} from 'storybook/test';

import {html} from 'lit';

import {sizes} from '@src/constants/size';

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
    size: {
      control: {type: 'inline-radio'},
      options: sizes,
      description: 'How large the strip is.',
    },
    selectedIndex: {
      name: 'selected-index',
      control: {type: 'number'},
      description: 'Index of the selected tab.',
    },
  },
  args: {
    layout: 'horizontal',
    size: 'medium',
    selectedIndex: 0,
  },
  render: (args) => html`
    <craft-tabs
      layout="${args.layout}"
      size="${args.size}"
      selected-index="${args.selectedIndex}"
    >
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

/**
 * The three sizes, along both axes. `size` sets a font size on the strip and
 * nothing else — the tabs are slotted, so they inherit it, and their padding is
 * `em`-based and follows. The panels keep the document's text size either way.
 */
export const Sizes: Story = {
  render: () => html`
    ${(['horizontal', 'vertical'] as const).map(
      (layout) => html`
        <div
          style="display: flex; align-items: start; gap: 2rem; margin-block-end: 2rem;"
        >
          ${sizes.map(
            (size) => html`
              <craft-tabs layout="${layout}" size="${size}">
                <craft-tab slot="tab">${size}</craft-tab>
                <div slot="panel"><p>A ${size} ${layout} strip.</p></div>
                <craft-tab slot="tab">Second</craft-tab>
                <div slot="panel"><p>Its second panel.</p></div>
              </craft-tabs>
            `
          )}
        </div>
      `
    )}
  `,
  play: async ({canvasElement}) => {
    const px = (tab: Element, property: string) =>
      parseFloat(getComputedStyle(tab).getPropertyValue(property));

    for (const layout of ['horizontal', 'vertical']) {
      const firstTabs = [
        ...canvasElement.querySelectorAll(`craft-tabs[layout="${layout}"]`),
      ].map((strip) => strip.querySelector('craft-tab')!);

      const fonts = firstTabs.map((tab) => px(tab, 'font-size'));
      const paddings = firstTabs.map((tab) => px(tab, 'padding-inline-start'));

      // Ordered small < medium < large...
      await expect(fonts[0]).toBeLessThan(fonts[1]!);
      await expect(fonts[1]).toBeLessThan(fonts[2]!);

      // ...and the padding tracks it, with no per-size padding rule involved.
      await expect(paddings[0]).toBeLessThan(paddings[1]!);
      await expect(paddings[1]).toBeLessThan(paddings[2]!);
      firstTabs.forEach((_, index) =>
        expect(paddings[index]).toBeCloseTo(fonts[index]!, 1)
      );
    }
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

/** Waits out the animation frames the overflow measurement schedules. */
async function settle() {
  for (let frame = 0; frame < 3; frame++) {
    await new Promise((resolve) => requestAnimationFrame(resolve));
  }
}

const OVERFLOW_LABELS = [
  'Content',
  'Metadata',
  'Search Engine Optimization',
  'Social Sharing',
  'Advanced Settings',
  'Permissions',
];

/**
 * A strip narrower than its tabs collapses the ones that don't fit into an
 * action menu at the end, keeping the selected tab in the strip. Drag the
 * Storybook viewport to watch tabs move in and out of the menu.
 */
export const Overflow: Story = {
  render: () => html`
    <div style="max-inline-size: 26rem; resize: horizontal; overflow: auto;">
      <craft-tabs>
        ${OVERFLOW_LABELS.map(
          (label, index) => html`
            <craft-tab slot="tab">${label}</craft-tab>
            <div slot="panel"><p>Panel ${index + 1}: ${label}</p></div>
          `
        )}
      </craft-tabs>
    </div>
  `,
  play: async ({canvasElement, userEvent}) => {
    const strip = canvasElement.querySelector('craft-tabs')!;
    const tabs = [...strip.querySelectorAll('craft-tab')];
    const menu = strip.shadowRoot!.querySelector<HTMLElement>(
      '[part="overflow-menu"]'
    )!;

    const collapsed = () => tabs.filter((tab) => tab.hasAttribute('hidden'));

    // The strip measures off a rAF, so settle before reading it.
    await settle();

    // Some tabs don't fit, so the menu is showing and holds exactly them.
    await expect(collapsed().length).toBeGreaterThan(0);
    await expect(menu.hidden).toBe(false);
    await expect(getComputedStyle(menu).display).not.toBe('none');

    // The visible tabs are a contiguous run from the start — collapsing never
    // reorders the strip.
    const visible = tabs.filter((tab) => !tab.hasAttribute('hidden'));
    await expect(tabs.slice(0, visible.length)).toEqual(visible);

    // Pick the last collapsed tab out of the menu.
    const target = collapsed().at(-1)!;
    const label = target.textContent!.trim();

    await userEvent.click(menu.querySelector('[slot="invoker"]')!);
    await settle();

    // The items are the menu's own light DOM, which lives inside the strip's
    // shadow root — a document-level query wouldn't reach them.
    const item = [...menu.querySelectorAll('craft-action-item')].find(
      (el) => el.textContent?.trim() === label
    );
    await expect(item).toBeTruthy();
    await userEvent.click(item as HTMLElement);
    await settle();

    // It swapped into the strip, selected, and something else took its place
    // in the menu.
    // The menu closes behind the selection, and focus lands on the tab that
    // just came back into the strip.
    await expect((menu as {opened?: boolean}).opened).toBe(false);
    await expect(target.hasAttribute('hidden')).toBe(false);
    await expect(target.getAttribute('aria-selected')).toBe('true');
    await expect(document.activeElement).toBe(target);
    await expect(collapsed().length).toBeGreaterThan(0);
  },
};

/** Everything fits, so no menu is shown. */
export const NoOverflow: Story = {
  render: () => html`
    <div style="max-inline-size: 60rem;">
      <craft-tabs>
        <craft-tab slot="tab">One</craft-tab>
        <div slot="panel"><p>First</p></div>
        <craft-tab slot="tab">Two</craft-tab>
        <div slot="panel"><p>Second</p></div>
      </craft-tabs>
    </div>
  `,
  play: async ({canvasElement}) => {
    const strip = canvasElement.querySelector('craft-tabs')!;
    const menu = strip.shadowRoot!.querySelector<HTMLElement>(
      '[part="overflow-menu"]'
    )!;

    await settle();

    await expect(menu.hidden).toBe(true);
    await expect(getComputedStyle(menu).display).toBe('none');
    await expect(
      [...strip.querySelectorAll('craft-tab')].some((t) =>
        t.hasAttribute('hidden')
      )
    ).toBe(false);
  },
};
