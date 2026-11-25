import {css, unsafeCSS} from 'lit';
import {
  segmentDividerStyles,
  segmentTrackStyles,
} from '../slide-picker/segment-track.styles.js';

export default css`
  :host {
    display: inline-flex;
  }

  :host([hidden]) {
    display: none;
  }

  ${segmentTrackStyles(unsafeCSS('.segments'))}

  .segments {
    --_pitch: calc(9rem / 16);
    --_split: calc(
      min(var(--_filled), 1) * 1px + var(--_filled) * var(--_pitch)
    );
    display: inline-flex;
    pointer-events: none;
  }

  .segments::before {
    border-color: var(--c-color-neutral-border-loud);
    background-color: var(
      --c-slide-picker-segment-active-fill,
      color-mix(
        in oklab,
        var(--c-color-neutral-fill-loud),
        var(--c-color-neutral-fill-normal) 70%
      )
    );
  }

  .segments::after {
    border-color: var(--c-color-neutral-border-normal);
    background-color: var(
      --c-slide-picker-segment-fill,
      var(--c-color-neutral-fill-quiet)
    );
  }

  .segment {
    position: relative;
    inline-size: var(--_pitch);
    block-size: 1rem;
  }

  .segment:first-child {
    inline-size: calc(var(--_pitch) + 1px);
  }

  .segment:not(:last-child)::after {
    --_divider: var(--c-color-neutral-border-quiet);
    ${segmentDividerStyles}
  }

  .segment.is-last-active:not(:last-child)::after {
    --_divider: var(--c-color-neutral-border-loud);
  }

  craft-popover::part(popup) {
    min-width: 0;
  }

  .content {
    display: grid;
    justify-items: center;
    gap: var(--c-spacing-xs);
    inline-size: min-content;
  }

  craft-slide-picker {
    --c-slide-picker-segment-width: calc(40rem / 16);
    --c-slide-picker-segment-height: calc(32rem / 16);
  }

  .label {
    font-size: var(--text-sm);
    text-align: center;
    overflow-wrap: break-word;
    text-box: trim-end cap alphabetic;
  }

  @supports not (text-box: trim-end cap alphabetic) {
    .label {
      margin-block-end: calc((1em - 1lh) / 2);
    }
  }

  @media (forced-colors: active) {
    .segments::before {
      border-color: Highlight;
      background-color: Highlight;
    }

    .segments::after {
      border-color: ButtonBorder;
      background-color: Canvas;
    }

    /* Forced colors would recolor the dividers to vanish on Highlight. */
    .segment:not(:last-child)::after {
      forced-color-adjust: none;
      --_divider: ButtonBorder;
    }

    .segment.is-active:not(:last-child)::after {
      --_divider: Canvas;
    }

    .segment.is-last-active:not(:last-child)::after {
      --_divider: Highlight;
    }
  }
`;
