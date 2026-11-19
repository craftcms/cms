import {css} from 'lit';
import {trimmedTextBoxStyles} from '@src/styles/text-box.styles';

/**
 * Masks a circle 2px wider than a dot out of the element it overlaps, so
 * whatever is behind both shows through instead of a ring in one surface
 * color. Include it in the rule for that element, and set
 * `--c-badge-indicator-cutout-x` and `--c-badge-indicator-cutout-y` to the
 * dot's center in that element's box.
 */
export const badgeIndicatorCutoutStyles = css`
  mask-image: radial-gradient(
    circle at var(--c-badge-indicator-cutout-x)
      var(--c-badge-indicator-cutout-y),
    transparent 6px,
    #000 6.5px
  );
`;

export default css`
  :host {
    display: inline-flex;
  }

  .badge-indicator {
    --_badge-color: var(--c-color-accent-fill-loud);
    --_text-color: var(--c-color-accent-on-loud);
    --badge-size: calc(8rem / 16);
    display: inline-flex;
    min-width: var(--badge-size);
    min-height: var(--badge-size);
    justify-content: center;
    align-items: center;
    background-color: var(--_badge-color);
    color: var(--_text-color);
    border-radius: var(--c-radius-full);
    /* No ring unless asked for, as where a marker overlaps something else. */
    border: var(--badge-border-width, 0) solid
      var(--badge-border-color, var(--c-surface-default));
  }

  .badge-indicator--secondary {
    --_badge-color: var(--c-color-warning-fill-loud);
  }

  .badge-indicator--inverse {
    --_badge-color: var(--c-color-neutral-fill-normal);
    --_text-color: var(--c-color-neutral-on-normal);
  }

  .badge-indicator--with-number {
    --badge-size: var(--c-size-icon-md);
    padding-block: calc(2rem / 16);
    /* Room either side of a longer count, so its digits don't touch the edge. */
    padding-inline: calc(4rem / 16);
  }

  .number {
    /* A block, so the trim applies; centers the digits on their cap height
       rather than on a line box that includes descender space. */
    display: block;
    font-size: var(--c-text-xs);
    font-weight: var(--font-weight-semibold);
    line-height: 1;
    font-variant-numeric: tabular-nums;
    ${trimmedTextBoxStyles}
  }
`;
