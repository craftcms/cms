import {css} from 'lit';
import {
  activeSegmentDividerForcedColorsStyles,
  segmentDividerStyles,
} from './segment-divider.styles.js';

export default css`
  :host {
    display: inline-flex;
  }

  /* The ring goes around the current segment; !important beats the global ring. */
  :host(:focus-visible) {
    outline: none !important;
  }

  .slide-picker {
    --_segment-height: var(--c-slide-picker-segment-height, 1rem);
    --_segment-width: var(--c-slide-picker-segment-width, calc(8rem / 16));
    --_segment-border: var(--c-color-neutral-border-normal);
    --_segment-active-border: var(--c-color-accent-border-loud);
    --_segment-divider: var(--c-color-accent-fill-quiet);
    --_segment-preview-border: var(--c-color-accent-border-loud);
    --_segment-fill: var(--c-slide-picker-segment-fill, transparent);
    --_segment-active-fill: var(
      --c-slide-picker-segment-active-fill,
      var(--c-color-accent-fill-loud)
    );
    --_segment-hover-fill: var(
      --c-slide-picker-segment-hover-fill,
      var(--c-color-accent-fill-quiet)
    );
    display: inline-flex;
    align-items: stretch;
    min-height: max(var(--_segment-height), var(--touch-target-size));
    outline: none;
  }

  .slide-picker__segment {
    position: relative;
    box-sizing: border-box;
    inline-size: calc(var(--_segment-width) + 1px);
    block-size: calc(var(--_segment-height) + 2px);

    border: 1px solid var(--_segment-border);
    border-inline-start-width: 0;
    background-color: var(--_segment-fill);
    cursor: pointer;
    margin-block: auto;
  }

  .slide-picker__segment:first-child {
    inline-size: calc(var(--_segment-width) + 2px);
    border-inline-start-width: 1px;
    border-start-start-radius: var(--c-radius-sm);
    border-end-start-radius: var(--c-radius-sm);
  }

  .slide-picker__segment:last-child {
    border-start-end-radius: var(--c-radius-sm);
    border-end-end-radius: var(--c-radius-sm);
  }

  .slide-picker__segment.is-active {
    background-color: var(--_segment-active-fill);
    border-color: var(--_segment-active-border);
  }

  .slide-picker__segment.is-active:not(.is-last-active) {
    border-inline-end-color: transparent;
  }

  .slide-picker__segment.is-active:not(.is-last-active)::after {
    --_divider-edge: var(--_segment-active-border);
    --_divider: var(--_segment-divider);
    ${segmentDividerStyles}
  }

  .slide-picker.is-previewing .slide-picker__segment {
    background-color: transparent;
    border-color: var(--_segment-border);
  }

  .slide-picker.is-previewing .slide-picker__segment::after {
    content: none;
  }

  .slide-picker.is-previewing .slide-picker__segment.is-previewed {
    background-color: var(--_segment-hover-fill);
    border-color: var(--_segment-preview-border);
    border-block-width: 2px;
    border-inline-end-width: 2px;
  }

  .slide-picker.is-previewing .slide-picker__segment.is-previewed:first-child {
    border-inline-start-width: 2px;
  }

  :host(:focus-visible) .slide-picker__segment.is-last-active {
    outline: var(--c-focus-outline-width) solid var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
    position: relative;
    z-index: 1;
  }

  :host([readonly]) .slide-picker__segment {
    cursor: default;
  }

  @media (forced-colors: active) {
    .slide-picker__segment {
      background: Canvas;
      border-color: ButtonBorder;
    }

    .slide-picker__segment.is-active {
      background: Highlight;
      border-color: Highlight;
    }

    .slide-picker__segment.is-active:not(.is-last-active)::after {
      ${activeSegmentDividerForcedColorsStyles}
    }

    .slide-picker.is-previewing .slide-picker__segment {
      background: Canvas;
      border-color: ButtonBorder;
    }

    .slide-picker.is-previewing .slide-picker__segment.is-previewed {
      background: Canvas;
      border-color: Highlight;
    }
  }
`;
