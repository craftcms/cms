import {css} from 'lit';

/**
 * The line between two slide picker segments, for a segment's `::after`.
 * Drawn over a transparent end border, so a lighter line doesn't cut into
 * the outline's corners. It stops inside the outline: `--_divider-edge`
 * colors its ends, where it crosses the outline, and `--_divider` the rest.
 */
export const segmentDividerStyles = css`
  content: '';
  position: absolute;
  inset-block: -1px;
  inset-inline-end: -1px;
  inline-size: 1px;
  background-image: linear-gradient(
    var(--_divider-edge) 1px,
    var(--_divider) 1px calc(100% - 1px),
    var(--_divider-edge) calc(100% - 1px)
  );
`;

/**
 * The divider between two filled segments in forced colors mode. Forced
 * colors would recolor it to vanish on their `Highlight` fill, so it opts out
 * and draws in `Canvas`, crossing the outline in `Highlight`.
 */
export const activeSegmentDividerForcedColorsStyles = css`
  forced-color-adjust: none;
  --_divider-edge: Highlight;
  --_divider: Canvas;
`;
