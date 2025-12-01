import {css} from 'lit';

export default css`
  :host {
    display: block;
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    border-radius: var(--c-callout-radius, var(--c-radius-md));
    background-color: var(--c-callout-bg);
    border: 1px solid var(--c-callout-border-color);
    color: var(--c-callout-fg);
  }

  :host([variant='danger']) {
    --c-callout-bg: var(--c-color-danger-bg-normal);
    --c-callout-border-color: var(--c-color-danger-border-normal);
    --c-callout-fg: var(--c-color-danger-on-normal);
  }
`;
