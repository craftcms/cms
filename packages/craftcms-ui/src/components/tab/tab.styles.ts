import {css} from 'lit';
import {trimmedTextBoxStyles} from '@src/styles/text-box.styles';

export default css`
  :host {
    display: inline-flex;
    padding-inline: var(--c-tab-padding-inline, 0);
    padding-block: var(--c-tab-padding-block, 0.5em);
    position: relative;
    /* Keeps the hover fill below behind the label, but not behind the page. */
    isolation: isolate;
    cursor: pointer;
  }

  /*
   * The display above is an author style, so it beats the UA's [hidden] rule —
   * without this a tab collapsed into <craft-tabs>' overflow menu would keep
   * its space in the strip.
   */
  :host([hidden]) {
    display: none;
  }

  /*
   * The selected indicator. Its geometry comes from custom properties so the
   * strip can move it without knowing anything about this shadow root: the
   * defaults below are the block-start placement (a rule underneath, pulled
   * 1px down to sit on top of the strip's border), and the other placements
   * publish the vars that move it to the edge facing the panels.
   */
  :host::after {
    content: '';
    position: absolute;
    display: block;
    background-color: transparent;
    inset-block-start: var(--c-tab-indicator-inset-block-start, auto);
    inset-block-end: var(--c-tab-indicator-inset-block-end, -1px);
    inset-inline-start: var(--c-tab-indicator-inset-inline-start, 0);
    inset-inline-end: var(--c-tab-indicator-inset-inline-end, 0);
    block-size: var(--c-tab-indicator-block-size, calc(2rem / 16));
    inline-size: var(--c-tab-indicator-inline-size, auto);
  }

  :host([selected])::after {
    background-color: var(
      --c-tab-indicator-fill-active,
      var(--c-color-accent-fill-loud)
    );
  }

  @media (forced-colors: active) {
    :host([selected])::after {
      background-color: Highlight;
    }
  }

  /*
   * The hover fill, like the sidebar nav's: shorter than the tab and centered
   * in it, so it stays clear of the strip's rule and the selected indicator.
   * It's drawn on ::before rather than the host's background for that reason.
   */
  @media (hover: hover) {
    :host(:hover:not([selected]):not([disabled]))::before {
      content: '';
      position: absolute;
      inset-inline: 0;
      inset-block: 0;
      /* The set height for a one-line label, growing a line at a time as the
         label wraps: the tab's content box less one line is the extra lines. */
      --_fill-block-size: var(--c-tab-fill-block-size, calc(30em / 14));
      block-size: min(
        100%,
        max(
          var(--_fill-block-size),
          100% - 2 * var(--c-tab-padding-block, 0.5em) - 1lh +
            var(--_fill-block-size)
        )
      );
      margin-block: auto;
      z-index: -1;
      border-radius: var(--c-radius-md);
      background-color: var(
        --c-tab-fill-hover,
        color-mix(in oklab, var(--c-color-mix-hover), transparent)
      );
    }
  }

  /* Forced colors drop the fill, so a hovered tab shows the indicator instead. */
  @media (forced-colors: active) {
    :host(:hover:not([selected]):not([disabled]))::after {
      background-color: CanvasText;
    }
  }

  :host([selected]) {
    cursor: default;
    font-weight: var(--c-tab-font-weight-active, var(--font-weight-semibold));
  }

  /* A hidden copy of the label in the selected weight reserves its width,
     so selecting a tab doesn't shift the strip. */
  .tab__label {
    display: inline-flex;
    flex-direction: column;
    min-width: 0;
  }

  ::slotted([slot='prefix']) {
    align-self: center;
    margin-inline-end: var(--c-spacing-sm);
  }

  ::slotted([slot='suffix']) {
    align-self: center;
    margin-inline-start: var(--c-spacing-sm);
  }

  /* Truncated only when <craft-tabs> asks, as it does for a selected tab that
     doesn't fit; otherwise a label wraps. */
  .tab__text {
    display: block;
    overflow: var(--_tab-text-overflow, visible);
    text-overflow: ellipsis;
    white-space: var(--_tab-text-wrap, normal);
    ${trimmedTextBoxStyles}
  }

  .tab__label::after {
    content: attr(data-text);
    block-size: 0;
    overflow: hidden;
    visibility: hidden;
    user-select: none;
    pointer-events: none;
    font-weight: var(--c-tab-font-weight-active, var(--font-weight-semibold));
  }

  /*
   * Out of hit-testing, not just dimmed: <craft-tabs> binds its click handler
   * to every tab, so a disabled tab that still takes pointer events would
   * select itself.
   */
  :host([disabled]) {
    cursor: default;
    /* A token rather than opacity, which dims the label below the contrast
       floor and reads as a real violation to auditing tools. */
    color: var(--c-tab-text-disabled, var(--c-status-disabled-text));
    pointer-events: none;
  }
`;
