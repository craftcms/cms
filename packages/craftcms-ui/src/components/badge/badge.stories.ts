import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {expect} from 'storybook/test';

import './badge.js';
import type CraftBadge from './badge.js';
import {Color, colors} from '@src/constants/colors';
import {capitalize} from '@src/utilities/string';
import '../icon/icon.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Badge',
  component: 'craft-badge',
  args: {
    fill: 'gray',
  },
  argTypes: {
    fill: {control: 'select', options: colors},
  },
  render: (args) => html`
    <craft-badge fill=${args.fill}>
      ${capitalize(String(args.fill))}
    </craft-badge>
  `,
} satisfies Meta<CraftBadge>;

export default meta;
type Story = StoryObj<CraftBadge>;

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
  render: () => html`<craft-badge fill="red">Value: red</craft-badge>`,
  play: async ({canvasElement}) => {
    const badge = canvasElement.querySelector('craft-badge')!;
    // The badge reflects the resolved color for its own surface styling.
    await expect(badge.dataset.color).toBe('red');
  },
};

export const AllColors: Story = {
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
  render: () => html`
    <craft-badge fill=${Color.Green}>
      <craft-icon slot="prefix" name="circle-check" label="Done"></craft-icon>
      Custom prefix
    </craft-badge>
  `,
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
