import {css} from 'lit';

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

  /* The outline around the whole picker and around its filled run holds
     3:1, so the picker's size and value show. The lines between segments
     stay quiet: they're decoration. */
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

  /* A quiet end border would cut into the outline's corners, so each line
     between segments is drawn over a transparent one and stops inside the
     outline. The filled run's last segment keeps its loud end border. */
  .segment:not(:last-child, .is-last-active) {
    border-inline-end-color: transparent;
  }

  .segment:not(:last-child, .is-last-active)::after {
    content: '';
    position: absolute;
    inset-block: -1px;
    inset-inline-end: -1px;
    inline-size: 1px;
    background-image: linear-gradient(
      var(--_edge) 1px,
      var(--c-color-neutral-border-quiet) 1px calc(100% - 1px),
      var(--_edge) calc(100% - 1px)
    );
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

    /* The themes' divider colors would be replaced, leaving a line that's
       hard to see on Highlight, so the lines are drawn in system colors. */
    .segment:not(:last-child, .is-last-active)::after {
      forced-color-adjust: none;
      background-image: linear-gradient(ButtonBorder, ButtonBorder);
    }

    .segment.is-active:not(:last-child, .is-last-active)::after {
      background-image: linear-gradient(
        Highlight 1px,
        Canvas 1px calc(100% - 1px),
        Highlight calc(100% - 1px)
      );
    }
  }
`;
