import {css} from 'lit';

export default css`
  :host {
    display: block;
    background-color: white;
    border-radius: var(--c-modal-radius);
    border-width: var(--c-modal-border-width);
    border-style: var(--c-modal-border-style);
    border-color: var(--c-modal-border-color);
  }

  .header {
    padding-inline: var(--c-spacing-lg);
    padding-block-start: var(--c-spacing-lg);
    padding-block-end: var(--c-spacing-md);
    font-size: 1.25em;
  }

  .body {
    padding: var(--c-spacing-lg);
  }

  .footer {
    padding-inline: var(--c-spacing-lg);
    padding-block-end: var(--c-spacing-lg);
  }
`;
