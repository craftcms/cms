import {css} from 'lit';

export default css`
  :host {
    display: contents;
    position: relative;
  }

  /* With no box of its own, the popover can't take a button group's overlap,
     so its invoker does. A craft-button invoker takes it itself, along with
     its own flush margin, which a margin set here would replace. */
  ::slotted([slot='invoker']:not(craft-button)) {
    margin-inline-start: var(--c-button-group-overlap);
  }

  ::slotted(:not([slot='invoker'])) {
    --c-button-group-overlap: initial;
  }

  .popover-pane {
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-overlay);
    color: var(--c-text-default);
    box-shadow: var(--c-shadow-sm);
    min-width: calc(180rem / 16);
    max-width: calc(320rem / 16);
    overflow: auto;
    overscroll-behavior: contain;
    --_fitted-block-size: var(--_popover-available-block-size, 40vh);
    max-height: var(--popover-max-block-size, var(--_fitted-block-size));
  }

  /* The overlay wrapper is the one given the invoker's width, so the pane has
     to stop sizing itself or it stays at its own 320px cap and the match does
     nothing visible. */
  :host([match-invoker-width]) .popover-pane {
    min-width: 0;
    max-width: none;
    width: 100%;
  }

  ::slotted([slot='content-body']) {
    padding: var(--c-spacing-md);
    display: grid;
    font-size: var(--c-text-base);
    font-weight: 400;
  }

  ::slotted([slot='content-footer']) {
    background-color: var(--c-color-neutral-fill-quiet);
    padding: var(--c-spacing-md);
    position: sticky;
    inset-block-end: 0;
    inset-inline-start: 0;
    inset-inline-end: 0;
  }
`;
