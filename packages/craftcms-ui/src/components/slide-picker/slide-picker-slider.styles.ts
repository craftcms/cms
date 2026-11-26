import {css, unsafeCSS} from 'lit';
import {
  segmentDividerStyles,
  segmentTrackStyles,
} from './segment-track.styles.js';

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
    align-items: center;
    min-height: max(var(--_segment-height), var(--touch-target-size));
    outline: none;
  }

  ${segmentTrackStyles(unsafeCSS('.slide-picker__track'))}

  .slide-picker__track {
    --_split: calc(
      min(var(--_filled), 1) * 1px + var(--_filled) *
        (var(--_segment-width) + 1px)
    );
    display: inline-flex;
  }

  .slide-picker__track::before {
    border-color: var(--_segment-active-border);
    background-color: var(--_segment-active-fill);
  }

  .slide-picker__track::after {
    border-color: var(--_segment-border);
    background-color: var(--_segment-fill);
  }

  .slide-picker__segment {
    position: relative;
    inline-size: calc(var(--_segment-width) + 1px);
    block-size: calc(var(--_segment-height) + 2px);
    cursor: pointer;
  }

  .slide-picker__segment:first-child {
    inline-size: calc(var(--_segment-width) + 2px);
  }

  .slide-picker__segment:not(:last-child)::after {
    --_divider: var(--_segment-border);
    ${segmentDividerStyles}
  }

  .slide-picker__segment.is-active:not(:last-child)::after {
    --_divider: var(--_segment-divider);
  }

  .slide-picker__segment.is-last-active:not(:last-child)::after {
    --_divider: var(--_segment-active-border);
  }

  .slide-picker.is-previewing .slide-picker__track::before {
    border-width: 2px;
    border-color: var(--_segment-preview-border);
    background-color: var(--_segment-hover-fill);
  }

  .slide-picker.is-previewing .slide-picker__track::after {
    background-color: transparent;
  }

  .slide-picker.is-previewing .slide-picker__segment:not(:last-child)::after {
    --_divider: var(--_segment-border);
  }

  .slide-picker.is-previewing
    .slide-picker__segment.is-previewed:not(:last-child)::after {
    --_divider: var(--_segment-preview-border);
    inline-size: 2px;
  }

  :host(:focus-visible) .slide-picker__segment.is-last-active {
    outline: var(--c-focus-outline-width) solid var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
    z-index: 1;
  }

  :host([readonly]) .slide-picker__segment {
    cursor: default;
  }

  @media (forced-colors: active) {
    .slide-picker__track::before {
      border-color: Highlight;
      background-color: Highlight;
    }

    .slide-picker__track::after {
      border-color: ButtonBorder;
      background-color: Canvas;
    }

    /* Forced colors would recolor the dividers to vanish on Highlight. */
    .slide-picker__segment:not(:last-child)::after {
      forced-color-adjust: none;
      --_divider: ButtonBorder;
    }

    .slide-picker__segment.is-active:not(:last-child)::after {
      --_divider: Canvas;
    }

    .slide-picker__segment.is-last-active:not(:last-child)::after {
      --_divider: Highlight;
    }

    .slide-picker.is-previewing .slide-picker__track::before {
      border-color: Highlight;
      background-color: Canvas;
    }

    .slide-picker.is-previewing .slide-picker__segment:not(:last-child)::after {
      --_divider: ButtonBorder;
    }

    .slide-picker.is-previewing
      .slide-picker__segment.is-previewed:not(:last-child)::after {
      --_divider: Highlight;
    }
  }
`;
