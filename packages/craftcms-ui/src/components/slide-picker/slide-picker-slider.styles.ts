import {css} from 'lit';

export default css`
  :host {
    display: inline-flex;
  }

  /* The ring goes around the current segment; !important beats the global ring. */
  :host(:focus-visible) {
    outline: none !important;
  }

  /* Every edge and fill that shows the value or the preview holds 3:1
     against the surface and against its neighbors, in both themes. */
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
    inline-size: var(--_segment-width);
    block-size: var(--_segment-height);

    border: 1px solid var(--_segment-border);
    border-inline-start-width: 0;
    background-color: var(--_segment-fill);
    cursor: pointer;
    margin-block: auto;
  }

  .slide-picker__segment:first-child {
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

  /* A light end border would cut into the outline's corners, so the divider
     between selected segments is drawn over a transparent one and stops
     inside the outline, keeping the segments countable. */
  .slide-picker__segment.is-active:not(.is-last-active) {
    border-inline-end-color: transparent;
  }

  .slide-picker__segment.is-active:not(.is-last-active)::after {
    content: '';
    position: absolute;
    inset-block: -1px;
    inset-inline-end: -1px;
    inline-size: 1px;
    background-image: linear-gradient(
      var(--_segment-active-border) 1px,
      var(--_segment-divider) 1px calc(100% - 1px),
      var(--_segment-active-border) calc(100% - 1px)
    );
  }

  /* While a value is previewed, every segment looks empty except the
     previewed run, which is outlined 2px thick so it differs from the
     selected run by shape, not just color. */
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

  /* The ring sits off the segment, on the surface: against the selected
     fill it would all but vanish. */
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

    /* The themes' divider colors would be replaced, leaving a line that's
       hard to see on Highlight, so it's drawn in system colors. */
    .slide-picker__segment.is-active:not(.is-last-active)::after {
      forced-color-adjust: none;
      background-image: linear-gradient(
        Highlight 1px,
        Canvas 1px calc(100% - 1px),
        Highlight calc(100% - 1px)
      );
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
