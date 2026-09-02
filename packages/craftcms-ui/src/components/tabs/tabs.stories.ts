import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import {expect, waitFor} from 'storybook/test';

import {html} from 'lit';

import {sizes} from '@src/constants/size';

import '../tab/tab.js';
import './tabs.js';
import type CraftTabs from './tabs.js';
import {tabsPlacements} from './tabs.js';

import '../icon/icon.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `tabs.ts` surfaces it here without touching this file.
 */
const {args, argTypes} = getStorybookHelpers<CraftTabs>('craft-tabs');

type CraftTabsArgs = CraftTabs & typeof args;

const meta = {
  title: 'Components/Tabs',
  component: 'craft-tabs',
  // `selected-index` is Lion's, so it is not in the manifest and is declared
  // alongside the generated set.
  argTypes: {
    ...argTypes,
    selectedIndex: {
      name: 'selected-index',
      control: {type: 'number'},
      description: 'Index of the selected tab. -1 selects nothing.',
    },
  },
  args: {
    ...args,
    placement: 'block-start',
    size: 'medium',
    selectedIndex: 0,
  },
  render: (args) => html`
    <craft-tabs
      placement="${args.placement}"
      size="${args.size}"
      selected-index="${args.selectedIndex}"
      ?collapsible="${args.collapsible}"
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
} satisfies Meta<CraftTabsArgs>;

export default meta;
type Story = StoryObj<CraftTabsArgs>;

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

/**
 * The four placements, in logical terms: the strip before the panels on the
 * block axis (the default) or after them, and the same two on the inline axis
 * — which is the shape an icon toolbar wants.
 */
export const Placements: Story = {
  render: () => html`
    <div style="display: grid; gap: 3rem;">
      ${tabsPlacements.map(
        (placement) => html`
          <craft-tabs placement="${placement}">
            <craft-tab slot="tab">First</craft-tab>
            <div slot="panel"><p>The strip is at the ${placement}.</p></div>
            <craft-tab slot="tab">Second</craft-tab>
            <div slot="panel"><p>Its second panel.</p></div>
          </craft-tabs>
        `
      )}
    </div>
  `,
  play: async ({canvasElement}) => {
    for (const placement of tabsPlacements) {
      const tabs = canvasElement.querySelector(
        `craft-tabs[placement="${placement}"]`
      )!;
      const box = (part: string) =>
        tabs
          .shadowRoot!.querySelector(`[part="${part}"]`)!
          .getBoundingClientRect();

      const strip = box('strip');
      const panels = box('panels');
      const orientation = tabs
        .shadowRoot!.querySelector('[part="tab-group"]')!
        .getAttribute('aria-orientation');

      if (placement === 'block-start') {
        await expect(orientation).toBe('horizontal');
        await expect(strip.bottom).toBeLessThanOrEqual(panels.top);
      } else if (placement === 'block-end') {
        await expect(orientation).toBe('horizontal');
        await expect(strip.top).toBeGreaterThanOrEqual(panels.bottom);
      } else if (placement === 'inline-start') {
        await expect(orientation).toBe('vertical');
        await expect(strip.right).toBeLessThanOrEqual(panels.left);
      } else {
        await expect(orientation).toBe('vertical');
        await expect(strip.left).toBeGreaterThanOrEqual(panels.right);
      }
    }
  },
};

/**
 * The placements are logical, not physical: `inline-start` is the left in LTR
 * and the right in RTL, and the rule and the selected indicator follow it
 * across on their own — there is no direction-specific CSS in the component.
 */
export const RightToLeft: Story = {
  render: () => html`
    <div dir="rtl" style="max-inline-size: 30rem;">
      <craft-tabs placement="inline-start">
        <craft-tab slot="tab">الأول</craft-tab>
        <div slot="panel"><p>اللوحة الأولى.</p></div>
        <craft-tab slot="tab">الثاني</craft-tab>
        <div slot="panel"><p>اللوحة الثانية.</p></div>
      </craft-tabs>
    </div>
  `,
  play: async ({canvasElement}) => {
    const tabs = canvasElement.querySelector('craft-tabs')!;
    const box = (part: string) =>
      tabs
        .shadowRoot!.querySelector(`[part="${part}"]`)!
        .getBoundingClientRect();

    // The strip is at the inline start, which here is the right-hand side.
    await expect(box('strip').left).toBeGreaterThanOrEqual(box('panels').right);
  },
};

/**
 * `layout` is deprecated in favour of `placement`, and kept as an alias over
 * it: `vertical` is `inline-start` and `horizontal` is `block-start`.
 */
export const DeprecatedLayout: Story = {
  render: () => html`
    <craft-tabs layout="vertical">
      <craft-tab slot="tab">First</craft-tab>
      <div slot="panel"><p>The first panel.</p></div>
      <craft-tab slot="tab">Second</craft-tab>
      <div slot="panel"><p>The second panel.</p></div>
    </craft-tabs>
  `,
  play: async ({canvasElement}) => {
    const tabs = canvasElement.querySelector('craft-tabs')!;

    await expect(tabs).toHaveAttribute('placement', 'inline-start');
    await expect(tabs.placement).toBe('inline-start');
    await expect(
      tabs.shadowRoot!.querySelector('[part="tab-group"]')
    ).toHaveAttribute('aria-orientation', 'vertical');
  },
};

/**
 * The three sizes, on both axes. `size` sets a font size on the strip and
 * nothing else — the tabs are slotted, so they inherit it, and their padding is
 * `em`-based and follows. The panels keep the document's text size either way.
 */
export const Sizes: Story = {
  render: () => html`
    ${(['block-start', 'inline-start'] as const).map(
      (placement) => html`
        <div
          style="display: flex; align-items: start; gap: 2rem; margin-block-end: 2rem;"
        >
          ${sizes.map(
            (size) => html`
              <craft-tabs placement="${placement}" size="${size}">
                <craft-tab slot="tab">${size}</craft-tab>
                <div slot="panel"><p>A ${size} ${placement} strip.</p></div>
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

    for (const placement of ['block-start', 'inline-start']) {
      const firstTabs = [
        ...canvasElement.querySelectorAll(
          `craft-tabs[placement="${placement}"]`
        ),
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

/**
 * Waits out the animation frames the overflow measurement schedules.
 *
 * A fixed number of frames is not enough on its own: `requestAnimationFrame`
 * is starved when several browser tests run at once, so the measurement had
 * not always finished by the time the assertions ran. Pair this with
 * `settleUntil()` wherever the result of a measurement is being read.
 */
async function settle() {
  for (let frame = 0; frame < 3; frame++) {
    await new Promise((resolve) => requestAnimationFrame(resolve));
  }
}

/** Settles, then waits for the measurement to actually land. */
async function settleUntil(condition: () => boolean) {
  await settle();
  await waitFor(() => expect(condition()).toBe(true));
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

    // The strip measures off a rAF, so wait for the measurement to land
    // rather than for a fixed number of frames.
    await settleUntil(() => collapsed().length > 0);

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
    await settleUntil(
      () => menu.querySelectorAll('craft-action-item').length > 0
    );

    // The items are the menu's own light DOM, which lives inside the strip's
    // shadow root — a document-level query wouldn't reach them.
    const item = [...menu.querySelectorAll('craft-action-item')].find(
      (el) => el.textContent?.trim() === label
    );
    await expect(item).toBeTruthy();
    await userEvent.click(item as HTMLElement);
    await settleUntil(() => !target.hasAttribute('hidden'));

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

/**
 * The target of `collapsible`: a rail of icon tabs that opens a panel beside
 * it, and closes again when you click the open tab — leaving nothing but the
 * rail. It starts closed here, which `selected-index="-1"` says.
 */
export const IconToolbar: Story = {
  render: () => html`
    <div style="display: flex; block-size: 14rem;">
      <craft-tabs
        placement="inline-start"
        size="small"
        collapsible
        selected-index="-1"
      >
        <craft-tab slot="tab">
          <craft-icon name="circle-info" label="Info"></craft-icon>
        </craft-tab>
        <div slot="panel" style="inline-size: 18rem; padding-inline: 1rem;">
          <p>Everything known about this thing.</p>
        </div>

        <craft-tab slot="tab">
          <craft-icon name="wave-pulse" label="Activity"></craft-icon>
        </craft-tab>
        <div slot="panel" style="inline-size: 18rem; padding-inline: 1rem;">
          <p>What has happened to it lately.</p>
        </div>

        <craft-tab slot="tab">
          <craft-icon name="clock-rotate-left" label="Revisions"></craft-icon>
        </craft-tab>
        <div slot="panel" style="inline-size: 18rem; padding-inline: 1rem;">
          <p>Every version of it.</p>
        </div>
      </craft-tabs>
    </div>
  `,
  play: async ({canvasElement, userEvent}) => {
    const strip = canvasElement.querySelector('craft-tabs')!;
    const tabs = [...strip.querySelectorAll('craft-tab')];
    const panels =
      strip.shadowRoot!.querySelector<HTMLElement>('[part="panels"]')!;
    const rail = strip
      .shadowRoot!.querySelector('[part="strip"]')!
      .getBoundingClientRect().width;
    const width = () => strip.getBoundingClientRect().width;

    // Closed: nothing selected, and the component is exactly the rail — the
    // panel region isn't holding an empty box open beside it.
    await expect(strip.selectedIndex).toBe(-1);
    await expect(getComputedStyle(panels).display).toBe('none');
    await expect(width()).toBeCloseTo(rail, 0);

    // The strip is still reachable by keyboard while it's closed.
    await expect(tabs[0]).toHaveAttribute('tabindex', '0');
    await expect(
      tabs.every((tab) => tab.getAttribute('aria-selected') === 'false')
    ).toBe(true);

    await userEvent.click(tabs[1]!);
    await strip.updateComplete;

    await expect(strip.selectedIndex).toBe(1);
    await expect(tabs[1]).toHaveAttribute('aria-selected', 'true');
    await expect(getComputedStyle(panels).display).not.toBe('none');
    await expect(width()).toBeGreaterThan(rail);

    // Clicking the open tab closes it again and gives the space back.
    await userEvent.click(tabs[1]!);
    await strip.updateComplete;

    await expect(strip.selectedIndex).toBe(-1);
    await expect(tabs[1]).toHaveAttribute('aria-selected', 'false');
    await expect(getComputedStyle(panels).display).toBe('none');
    await expect(width()).toBeCloseTo(rail, 0);

    // ...and the tab it closed from keeps the tab order.
    await expect(tabs[1]).toHaveAttribute('tabindex', '0');

    // Escape is the keyboard's way out of an open strip.
    await userEvent.click(tabs[2]!);
    await strip.updateComplete;
    await expect(strip.selectedIndex).toBe(2);

    tabs[2]!.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Escape', bubbles: true})
    );
    await strip.updateComplete;

    await expect(strip.selectedIndex).toBe(-1);
  },
};
