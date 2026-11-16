import {css} from 'lit';
import {segmentDividerStyles} from '../slide-picker/segment-divider.styles.js';

export default css`
  :host {
    display: inline-flex;
  }

  :host([hidden]) {
    display: none;
  }

  .segments {
    display: inline-flex;
    pointer-events: none;
  }

  .segment {
    --_edge: var(--c-color-neutral-border-normal);
    position: relative;
    box-sizing: border-box;
    inline-size: calc(9rem / 16);
    block-size: 1rem;
    border: 1px solid var(--_edge);
    border-inline-start-width: 0;
    background-color: var(
      --c-slide-picker-segment-fill,
      var(--c-color-neutral-fill-quiet)
    );
  }

  .segment:first-child {
    border-inline-start-width: 1px;
    border-start-start-radius: var(--c-radius-sm);
    border-end-start-radius: var(--c-radius-sm);
  }

  .segment:last-child {
    border-start-end-radius: var(--c-radius-sm);
    border-end-end-radius: var(--c-radius-sm);
  }

  .segment.is-active {
    --_edge: var(--c-color-neutral-border-loud);
    background-color: var(
      --c-slide-picker-segment-active-fill,
      color-mix(
        in oklab,
        var(--c-color-neutral-fill-loud),
        var(--c-color-neutral-fill-normal) 70%
      )
    );
  }

  .segment:not(:last-child, .is-last-active) {
    border-inline-end-color: transparent;
  }

  .segment:not(:last-child, .is-last-active)::after {
    --_divider-edge: var(--_edge);
    --_divider: var(--c-color-neutral-border-quiet);
    ${segmentDividerStyles}
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
    .segment {
      background: Canvas;
      border-color: ButtonBorder;
    }

    .segment.is-active {
      background: Highlight;
      border-color: Highlight;
    }

    /* Forced colors would recolor the dividers to vanish on Highlight. */
    .segment:not(:last-child, .is-last-active)::after {
      forced-color-adjust: none;
      --_divider-edge: ButtonBorder;
      --_divider: ButtonBorder;
    }

    .segment.is-active:not(:last-child, .is-last-active)::after {
      --_divider-edge: Highlight;
      --_divider: Canvas;
    }
  }
`;
