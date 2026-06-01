import {css} from 'lit';

export default css`
  :host {
    display: inline-block;
    position: relative;
  }

  ::slotted([slot='content']) {
    font-size: var(--c-text-base);
    font-weight: 400;
    display: grid;
    gap: var(--c-spacing-xs);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-overlay);
    box-shadow: var(--c-shadow-sm);
    padding: var(--c-spacing-sm);
    min-width: calc(180rem / 16);
    max-width: calc(320rem / 16);
  }
`;
