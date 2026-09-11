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

/**
 * The graduations fade towards the edges by masking their own alpha, not by
 * painting a gradient of the page background over them. The overlay this
 * replaces was hard-coded to `--gray-900`, so anywhere lighter got a dark band
 * smeared across both ends instead of a fade — which this story would show.
 */
export const OnALightBackground: Story = {
  args: {},
  decorators: [
    (story) =>
      html`<div style="background: #fff; padding: 1rem;">${story()}</div>`,
  ],
  async play({canvasElement}) {
    const rule = canvasElement.querySelector(
      'craft-slide-rule'
    ) as CraftSlideRule;
    await rule.updateComplete;

    const graduations =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations')!;

    // Nothing paints over the graduations any more.
    await expect(rule.shadowRoot!.querySelector('.overlay')).toBe(null);

    // A real browser resolves the mask, so this checks more than the
    // stylesheet text the happy-dom test can see.
    const mask = getComputedStyle(graduations).maskImage;

    await expect(mask).not.toBe('none');

    // Resolved, so `transparent` comes back as zero-alpha black: see-through
    // at both ends, fully opaque across the middle where the value is read.
    await expect(mask).toBe(
      'linear-gradient(to right, rgba(0, 0, 0, 0) 0%, rgb(0, 0, 0) 15%, ' +
        'rgb(0, 0, 0) 85%, rgba(0, 0, 0, 0) 100%)'
    );
  },
};

/**
 * The graduations size from `--c-slide-rule-*` tokens. The width is the
 * load-bearing one: the strip's positioning is in units of one graduation, so
 * the component measures what was rendered rather than trusting the default.
 * Assume the default and a wider graduation slides zero out from under the
 * cursor — the same failure the whitespace between inline-block graduations
 * used to cause.
 */
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

/**
 * The ticks, labels and accent all come from tokens now, so the rule follows
 * the CP's scheme instead of the legacy image editor's hard-coded `#63a6e1`
 * and `--white`.
 */
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
 * The colours are tokens, so they re-resolve per theme rather than staying at
 * the legacy image editor's hard-coded `#63a6e1` and `--white` — which only
 * ever read correctly on a dark background.
 */
export const AcrossThemes: Story = {
  args: {},
  decorators: [
    (story) => html`
      <div
        data-theme="light"
        style="padding: 1rem; background: var(--c-surface-default)"
      >
        ${story()}
      </div>
      <div
        data-theme="dark"
        style="padding: 1rem; background: var(--c-surface-default)"
      >
        ${story()}
      </div>
    `,
  ],
  async play({canvasElement}) {
    const [light, dark] = [
      ...canvasElement.querySelectorAll('craft-slide-rule'),
    ] as CraftSlideRule[];

    await light.updateComplete;
    await dark.updateComplete;

    const tickColor = (rule: CraftSlideRule) =>
      getComputedStyle(
        rule.shadowRoot!.querySelector<HTMLElement>('.graduation')!
      ).backgroundColor;

    // Dark slate on light, light slate on dark — a hard-coded colour would
    // come back the same in both.
    await expect(tickColor(light)).toBe('rgb(58, 69, 90)');
    await expect(tickColor(dark)).toBe('rgb(203, 213, 224)');

    const cursorColor = (rule: CraftSlideRule) =>
      getComputedStyle(rule.shadowRoot!.querySelector<HTMLElement>('.cursor')!)
        .borderBlockStartColor;

    await expect(cursorColor(light)).toBe('rgb(33, 56, 167)');
    await expect(cursorColor(dark)).toBe('rgb(188, 213, 251)');
  },
};

/**
 * The cursor sits in its own grid row above the ruler, centred by the layout
 * rather than by `left: 50%` and a margin pulling it back by half its width.
 * That margin was a magic -4px against a 10px-wide triangle, so the cursor
 * landed a pixel off the centre the strip is positioned against — and a tap,
 * which measures its delta from the cursor, inherited the error.
 */
export const CursorCentring: Story = {
  args: {},
  async play({canvasElement}) {
    const rule = canvasElement.querySelector(
      'craft-slide-rule'
    ) as CraftSlideRule;
    await rule.updateComplete;

    const cursor = rule.shadowRoot!.querySelector<HTMLElement>('.cursor')!;
    const graduations =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations')!;

    const cursorBox = cursor.getBoundingClientRect();
    const windowBox = graduations.getBoundingClientRect();

    // The triangle is drawn out of the borders of a zero-width box, so its
    // centre is half the border box across — the same point the JS uses.
    const cursorCentre = cursorBox.left + cursor.offsetWidth / 2;
    const windowCentre = windowBox.left + graduations.offsetWidth / 2;

    await expect(Math.abs(cursorCentre - windowCentre)).toBeLessThan(0.5);

    // And the mark it points at is centred on that same point. A graduation is
    // a position on the ruler, so the mark straddles it -- begin the mark
    // there instead and it sits half its own thickness to the right, which is
    // the cursor pointing into the gap beside it.
    const zero = rule.shadowRoot!.querySelector<HTMLElement>(
      '[data-graduation="0"]'
    )!;
    const zeroBox = zero.getBoundingClientRect();

    await expect(
      Math.abs(zeroBox.left + zeroBox.width / 2 - cursorCentre)
    ).toBeLessThan(0.5);

    // Its own row, so it sits above the ruler rather than over the ticks, with
    // a little air between it and the marks it points at.
    await expect(windowBox.top - cursorBox.bottom).toBeCloseTo(4, 1);

    // And that air is taken out of the space above the cursor, not added to
    // the total -- the control is still the height the token asks for.
    const root = rule.shadowRoot!.querySelector<HTMLElement>('.slide-rule')!;

    await expect(
      windowBox.bottom - root.getBoundingClientRect().bottom
    ).toBeCloseTo(0, 1);

    // The focus ring still anchors to the cursor, which is the only reason
    // the cursor is positioned at all now.
    const ring = getComputedStyle(cursor, '::after');

    await expect(ring.position).toBe('absolute');
    await expect(parseFloat(ring.width)).toBeGreaterThan(0);
  },
};

/**
 * Each graduation is the mark, not a box with a pseudo-element drawing one.
 * The grid track supplies the spacing the box used to, so the item is free to
 * be the tick — which also means the marks are now a couple of pixels wide,
 * and the strip behind them has to stay the thing a drag lands on.
 */
export const GraduationIsTheMark: Story = {
  args: {},
  async play({canvasElement}) {
    const rule = canvasElement.querySelector(
      'craft-slide-rule'
    ) as CraftSlideRule;
    await rule.updateComplete;

    const minor = rule.shadowRoot!.querySelector<HTMLElement>(
      '[data-graduation="1"]'
    )!;
    const major = rule.shadowRoot!.querySelector<HTMLElement>(
      '[data-graduation="5"]'
    )!;

    // Nothing is drawn by a pseudo-element any more.
    await expect(getComputedStyle(minor, '::before').content).toBe('none');

    // The element is the tick: 2px by 6px, twice as thick and half again as
    // tall every fifth mark.
    await expect(minor.offsetWidth).toBe(2);
    await expect(minor.offsetHeight).toBe(6);
    await expect(major.offsetWidth).toBe(4);
    await expect(major.offsetHeight).toBe(9);

    // Both sit on the same centre line, which is what the strip is positioned
    // against — the thinner mark is inset by half the difference.
    const centre = (el: HTMLElement) =>
      el.getBoundingClientRect().left + el.offsetWidth / 2;

    await expect(centre(major) - centre(minor)).toBeCloseTo(40, 1);

    // Exactly one graduation apart, thick mark to thin -- they share a centre
    // line because each is centred on its own point, not nudged onto one.
    const next = rule.shadowRoot!.querySelector<HTMLElement>(
      '[data-graduation="2"]'
    )!;

    await expect(centre(next) - centre(minor)).toBeCloseTo(10, 1);

    // The label clears the mark rather than butting against it, and still
    // ends up inside the clip -- the floor on the graduations accounts for
    // the gap as well as the label.
    const label = rule.shadowRoot!.querySelector<HTMLElement>(
      '.main-graduation .label'
    )!;
    const window_ =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations')!;

    await expect(
      label.getBoundingClientRect().top - major.getBoundingClientRect().bottom
    ).toBeCloseTo(2, 1);
    await expect(
      window_.getBoundingClientRect().bottom -
        label.getBoundingClientRect().bottom
    ).toBeGreaterThan(0);

    // A press in the gap between two marks still has something to land on:
    // the strip covers the window, and only the window itself is excluded.
    const graduations =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations')!;
    const strip =
      rule.shadowRoot!.querySelector<HTMLElement>('.graduations ul')!;
    const box = minor.getBoundingClientRect();

    const inTheGap = rule.shadowRoot!.elementFromPoint(
      box.right + 3,
      box.top + 2
    )!;

    await expect(inTheGap).not.toBe(minor);
    await expect(graduations.contains(inTheGap)).toBe(true);
    await expect(inTheGap).not.toBe(graduations);
    await expect(strip.contains(inTheGap) || inTheGap === strip).toBe(true);
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
