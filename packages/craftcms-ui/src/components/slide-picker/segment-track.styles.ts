import {css, type CSSResult} from 'lit';

/**
 * The outline and fill of a slide picker's track, the element around its
 * segments. Two boxes the size of the track draw them: `::before` the filled
 * run and `::after` the rest, each clipped to its own part, which ends or
 * starts `--_split` from the track's inline start. Their rounded edges have
 * the same geometry, so they land on the same device pixels at any zoom or
 * display scaling. Style each box's `border-color` and `background-color`.
 */
export function segmentTrackStyles(track: CSSResult): CSSResult {
  return css`
    ${track} {
      position: relative;
      isolation: isolate;
    }

    ${track}::before, ${track}::after {
      content: '';
      position: absolute;
      inset: 0;
      z-index: -1;
      box-sizing: border-box;
      border: 1px solid;
      border-radius: var(--c-radius-sm);
    }

    ${track}::before {
      clip-path: inset(0 calc(100% - var(--_split)) 0 0);
    }

    ${track}::after {
      clip-path: inset(0 0 0 var(--_split));
    }

    :host(:dir(rtl)) ${track}::before {
      clip-path: inset(0 0 0 calc(100% - var(--_split)));
    }

    :host(:dir(rtl)) ${track}::after {
      clip-path: inset(0 var(--_split) 0 0);
    }
  `;
}

/**
 * The line between two segments, for a segment's `::after`. It's drawn in
 * `--_divider` over the segment's last pixel, inside the track's outline.
 */
export const segmentDividerStyles = css`
  content: '';
  position: absolute;
  inset-block: 1px;
  inset-inline-end: 0;
  inline-size: 1px;
  background-color: var(--_divider);
`;
