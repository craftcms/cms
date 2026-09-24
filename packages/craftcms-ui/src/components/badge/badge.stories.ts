import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {expect} from 'storybook/test';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './badge.js';
import type CraftBadge from './badge.js';
import {Color} from '@src/constants/colors';
import {capitalize} from '@src/utilities/string';
import '../icon/icon.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `badge.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftBadge>('craft-badge');

type BadgeArgs = CraftBadge & typeof args;

const meta = {
  title: 'Components/Badge',
  component: 'craft-badge',
  args: {...args, fill: 'gray', 'default-slot': 'Gray'},
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<BadgeArgs>;

export default meta;
type Story = StoryObj<BadgeArgs>;

export const Default: Story = {
  play: async ({canvasElement}) => {
    const host = canvasElement.querySelector('craft-badge')!;
    // The default prefix renders an indicator …
    const indicator = host.shadowRoot!.querySelector('craft-indicator')!;
    await expect(indicator).toBeTruthy();
    // … which receives the badge's color and resolves it to the loud tone …
    await expect(indicator.getAttribute('fill')).toBe('gray');
    const dot = indicator.shadowRoot!.querySelector('.indicator')!;
    await expect(dot.getAttribute('style')).toContain(
      'var(--c-color-gray-fill-loud)'
    );
    // … and the host reflects the resolved color for its own surface styling.
    await expect(host.dataset.color).toBe('gray');
  },
};

/** `fill` takes a color value from `Color` (e.g. `red`). */
export const FromColorValue: Story = {
  args: {fill: 'red', 'default-slot': 'Value: red'},
  play: async ({canvasElement}) => {
    const badge = canvasElement.querySelector('craft-badge')!;
    // The badge reflects the resolved color for its own surface styling.
    await expect(badge.dataset.color).toBe('red');
  },
};

export const AllColors: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: grid; gap: 0.5rem; justify-items: start">
      ${Object.entries(Color).map(
        ([key, value]) =>
          html`<craft-badge fill=${value}>${capitalize(key)}</craft-badge>`
      )}
    </div>
  `,
};

export const SemanticColors: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: grid; gap: 0.5rem; justify-items: start">
      ${(
        [
          ['Neutral', Color.Neutral],
          ['Accent', Color.Accent],
          ['Success', Color.Success],
          ['Warning', Color.Warning],
          ['Danger', Color.Danger],
          ['Info', Color.Info],
        ] as const
      ).map(
        ([label, value]) =>
          html`<craft-badge fill=${value}>${label}</craft-badge>`
      )}
    </div>
  `,
};

export const CustomPrefix: Story = {
  args: {
    fill: Color.Green,
    'prefix-slot': '<craft-icon name="circle-check" label="Done"></craft-icon>',
    'default-slot': 'Custom prefix',
  },
  play: async ({canvasElement}) => {
    const host = canvasElement.querySelector('craft-badge')!;
    // A slotted prefix overrides the default indicator fallback.
    const slot = host.shadowRoot!.querySelector<HTMLSlotElement>(
      'slot[name="prefix"]'
    )!;
    const [prefix] = slot.assignedElements();
    await expect(prefix).toBeTruthy();
    await expect(prefix?.tagName.toLowerCase()).toBe('craft-icon');
  },
};
