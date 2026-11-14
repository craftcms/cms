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

  .segment {
    box-sizing: border-box;
    inline-size: calc(9rem / 16);
    block-size: 1rem;
    border: 1px solid var(--c-color-neutral-border-quiet);
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
    background-color: var(
      --c-slide-picker-segment-active-fill,
      color-mix(
        in oklab,
        var(--c-color-neutral-fill-loud),
        var(--c-color-neutral-fill-normal) 70%
      )
    );
    border-color: var(--c-color-neutral-border-loud);
  }

  .segment.is-active:not(.is-last-active) {
    border-inline-end-color: var(--c-color-neutral-border-quiet);
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
  }
`;
