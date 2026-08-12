import {css} from 'lit';

export default css`
  :host {
    display: contents;
  }

  .text-expander__popup {
    max-width: min(calc(360rem / 16), calc(100vw - var(--c-spacing-lg)));
    max-height: 40vh;
    overflow: auto;
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-overlay);
    box-shadow: var(--c-shadow-sm);
    padding: var(--c-spacing-sm);
  }

  ::slotted([slot='listbox']) {
    display: grid;
    gap: var(--c-spacing-xs);
    outline: none;
  }

  .text-expander__loading {
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    color: var(--c-text-quiet);
  }

  .cp-visually-hidden:not(:focus-within) {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    clip: rect(0 0 0 0) !important;
    clip-path: inset(50%) !important;
    border: none !important;
    overflow: hidden !important;
    white-space: nowrap !important;
    padding: 0 !important;
  }
`;
