import {css} from 'lit';

export default css`
  :host {
    display: inline-block;
  }

  .slide-picker {
    --_segment-height: 1rem;
    --_segment-width: calc(8rem / 16);
    --_segment-border: var(--c-color-neutral-border-normal);
    --_segment-active-border: var(--c-color-neutral-border-loud);
    --_segment-fill: var(--c-color-neutral-fill-quiet);
    --_segment-active-fill: var(--c-color-neutral-fill-normal);
    display: inline-flex;
    align-items: stretch;
    min-height: max(var(--_segment-height), var(--touch-target-size));
    outline: none;
  }

  .slide-picker__segment {
    inline-size: var(--_segment-width);
    block-size: calc(var(--_segment-height) * 0.75);

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
    border-block-color: var(--_segment-active-border);
    block-size: var(--_segment-height);
  }

  .slide-picker__segment.is-last-active {
    border-inline-end-color: var(--_segment-active-border);
  }

  .slide-picker:focus-visible .slide-picker__segment.is-last-active {
    outline: var(--c-focus-outline-width) solid var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
    position: relative;
    z-index: var(--c-z-raised, 1);
  }

  :host([read-only]) .slide-picker__segment {
    cursor: default;
    opacity: 0.7;
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
  }
`;
