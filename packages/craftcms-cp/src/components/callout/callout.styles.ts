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
  
  :host([flash]) {
    border-start-start-radius: 0;
    border-start-end-radius: 0;
    border-top: 0;
  }

  :host([variant='danger']) {
    --c-callout-bg: var(--c-color-danger-bg-normal);
    --c-callout-border-color: var(--c-color-danger-border-normal);
    --c-callout-fg: var(--c-color-danger-on-normal);
  }
  
  :host([variant='success']) {
    --c-callout-bg: var(--c-color-success-bg-normal);
    --c-callout-border-color: var(--c-color-success-border-normal);
    --c-callout-fg: var(--c-color-success-on-normal);
  }
`;
