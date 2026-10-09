import {css} from 'lit';
import {trimmedTextBoxStyles} from '@src/styles/text-box.styles';

export default css`
  :host {
    display: inline-flex;
    padding-inline: var(--c-tab-spacing-inline, 0);
    padding-block: var(--c-tab-spacing-block, 0.5em);
    position: relative;
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
      --c-tab-border-active,
      var(--c-color-accent-fill-loud)
    );
  }

  @media (forced-colors: active) {
    :host([selected])::after {
      background-color: Highlight;
    }
  }

  :host([selected]) {
    font-weight: var(--c-tab-font-weight-active, var(--font-weight-semibold));
  }

  /* A hidden copy of the label in the selected weight reserves its width,
     so selecting a tab doesn't shift the strip. */
  .tab__label {
    display: inline-flex;
    flex-direction: column;
    min-width: 0;
  }

  .tab__text {
    display: block;
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
