import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect} from 'storybook/test';
import type CraftSlideRule from './slide-rule.js';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './slide-rule.js';

const {events, args, argTypes, template} =
  getStorybookHelpers('craft-slide-rule');

const meta: Meta<CraftSlideRule> = {
  title: 'Controls/Slide Rule',
  component: 'craft-slide-rule',
  args,
  argTypes,
  render: (args) => template(args),
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<CraftSlideRule & typeof args>;

export const Default: Story = {
  args: {},
};

export const StartingAngle: Story = {
  args: {
    value: 15,
  },
};

/**
 * The cursor is pinned to the middle of the rule, and the strip is centred on
 * the width of the window it slides behind — so the two only agree while that
 * window is the rule's width rather than the strip's. It used to size to its
 * content, leaving the strip centred on a box wider than the visible one and
 * the cursor sitting about 20 degrees off zero.
 */
export const CentredOnZero: Story = {
  args: {},
  async play({canvasElement}) {
    const rule = canvasElement.querySelector(
      'craft-slide-rule'
    ) as CraftSlideRule;
    await rule.updateComplete;

    const graduations =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations')!;
    const strip =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations ul')!;

    // The window is the rule, not the strip behind it.
    await expect(graduations.offsetWidth).toBeLessThanOrEqual(rule.offsetWidth);
    await expect(strip.offsetWidth).toBeGreaterThan(graduations.offsetWidth);

    // And zero lands under the cursor, at the middle of the window.
    const zero = rule.shadowRoot!.querySelector<HTMLElement>(
      '[data-graduation="0"]'
    )!;
    const offset =
      zero.getBoundingClientRect().left -
      graduations.getBoundingClientRect().left;

    await expect(Math.abs(offset - graduations.offsetWidth / 2)).toBeLessThan(
      6
    );

    // Each graduation is 10px, which is what the positioning maths assumes.
    await expect(strip.offsetWidth).toBe(141 * 10);
  },
};
