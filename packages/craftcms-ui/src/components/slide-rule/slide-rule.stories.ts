import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
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

export const CustomGraduations: Story = {
  args: {},
  decorators: [
    (story) => html`
      <div
        style="
          --c-slide-rule-graduation-width: 16px;
          --c-slide-rule-graduation-height: 10px;
          --c-slide-rule-height: 52px;
        "
      >
        ${story()}
      </div>
    `,
  ],
  async play({canvasElement}) {
    const rule = canvasElement.querySelector(
      'craft-slide-rule'
    ) as CraftSlideRule;
    await rule.updateComplete;

    const graduations =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations')!;
    const strip =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations ul')!;

    // The token reached the graduations, so the strip is wider than default.
    await expect(strip.offsetWidth).toBe(141 * 16);

    // The height token is the whole control; the graduations take whatever the
    // cursor row and the gap above them leave, down to the bottom of it.
    await expect(rule.offsetHeight).toBe(52);

    const cursor = rule.shadowRoot!.querySelector<HTMLElement>('.cursor')!;
    const gap =
      graduations.getBoundingClientRect().top -
      cursor.getBoundingClientRect().bottom;

    await expect(gap).toBeCloseTo(4, 1);
    await expect(
      graduations.getBoundingClientRect().bottom -
        rule.getBoundingClientRect().bottom
    ).toBeCloseTo(0, 1);

    // Every fifth tick is half again as tall, derived from the one token.
    const tickHeight = (selector: string) =>
      getComputedStyle(rule.shadowRoot!.querySelector<HTMLElement>(selector)!)
        .blockSize;

    await expect(tickHeight('[data-graduation="1"]')).toBe('10px');
    await expect(tickHeight('[data-graduation="5"]')).toBe('15px');

    // And zero still lands under the cursor, which only holds if the maths
    // measured 16px rather than assuming 10.
    const zero = rule.shadowRoot!.querySelector<HTMLElement>(
      '[data-graduation="0"]'
    )!;
    const offset =
      zero.getBoundingClientRect().left -
      graduations.getBoundingClientRect().left;

    await expect(Math.abs(offset - graduations.offsetWidth / 2)).toBeLessThan(
      10
    );
  },
};

export const CustomColors: Story = {
  args: {value: 12},
  decorators: [
    (story) => html`
      <div
        style="
          --c-slide-rule-graduation-color: #7c3aed;
          --c-slide-rule-accent-color: #f97316;
          color: #7c3aed;
        "
      >
        ${story()}
      </div>
    `,
  ],
  async play({canvasElement}) {
    const rule = canvasElement.querySelector(
      'craft-slide-rule'
    ) as CraftSlideRule;
    await rule.updateComplete;

    const cursor = rule.shadowRoot!.querySelector<HTMLElement>('.cursor')!;
    const tick = rule.shadowRoot!.querySelector<HTMLElement>('.graduation')!;

    await expect(getComputedStyle(cursor).borderBlockStartColor).toBe(
      'rgb(249, 115, 22)'
    );
    await expect(getComputedStyle(tick).backgroundColor).toBe(
      'rgb(124, 58, 237)'
    );

    // Labels have no colour of their own — they take whatever text colour
    // they land in, so they can never be the one thing that doesn't match.
    const label = rule.shadowRoot!.querySelector<HTMLElement>(
      '.main-graduation .label'
    )!;

    await expect(getComputedStyle(label).color).toBe('rgb(124, 58, 237)');

    // And the digits are monospaced, so they don't jitter as the strip slides.
    await expect(getComputedStyle(label).fontFamily).toContain('monospace');
  },
};

/**
 * The span between zero and the current value, in the accent fill a selected
 * table row or menu item uses. It's a band rather than lit-up graduations
 * because the value is continuous: 12.4 degrees falls between two marks, and
 * there is nothing there to light up.
 */
export const ValueIndicator: Story = {
  args: {value: 12.4},
  async play({canvasElement}) {
    const rule = canvasElement.querySelector(
      'craft-slide-rule'
    ) as CraftSlideRule;
    await rule.updateComplete;

    const indicator =
      rule.shadowRoot!.querySelector<HTMLElement>('.indicator')!;
    const cursor = rule.shadowRoot!.querySelector<HTMLElement>('.cursor')!;
    const zero = rule.shadowRoot!.querySelector<HTMLElement>(
      '[data-graduation="0"]'
    )!;

    const box = indicator.getBoundingClientRect();
    const cursorCentre =
      cursor.getBoundingClientRect().left + cursor.offsetWidth / 2;
    const zeroCentre =
      zero.getBoundingClientRect().left +
      zero.getBoundingClientRect().width / 2;

    // It runs from the mark for zero to the value under the cursor, landing
    // 4px past the 12th graduation rather than on it.
    await expect(box.left).toBeCloseTo(zeroCentre, 0);
    await expect(box.right).toBeCloseTo(cursorCentre, 0);
    await expect(box.width).toBeCloseTo(124, 0);

    // The same accent a selected row takes, so a selection reads the same
    // wherever it is in the CP.
    const style = getComputedStyle(indicator);
    const accent = getComputedStyle(rule).getPropertyValue(
      '--c-color-accent-fill-quiet'
    );

    await expect(accent.trim()).not.toBe('');
    await expect(style.backgroundColor).not.toBe('rgba(0, 0, 0, 0)');

    // And it sits behind the marks rather than over them.
    const atZero = rule.shadowRoot!.elementFromPoint(
      zeroCentre,
      zero.getBoundingClientRect().top + 2
    );

    await expect(atZero).toBe(zero);
  },
};

/**
 * At zero the band has no width, so all that would show is the pair of
 * borders standing either side of the cursor. It stays out of the way until
 * there is a value to describe.
 */
export const NoValueYet: Story = {
  args: {value: 0},
  async play({canvasElement}) {
    const rule = canvasElement.querySelector(
      'craft-slide-rule'
    ) as CraftSlideRule;
    await rule.updateComplete;

    const indicator =
      rule.shadowRoot!.querySelector<HTMLElement>('.indicator')!;

    // Nothing in the sheet sets `display` on it, so `hidden` is free to.
    await expect(getComputedStyle(indicator).display).toBe('none');
    await expect(indicator.getBoundingClientRect().width).toBe(0);
  },
};
