import {css} from 'lit';

export default css`
  :host {
    display: inline-flex;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    background-color: var(--c-color-fill-quiet);
    border: 1px solid var(--c-color-border-quiet);
    color: var(--c-color-on-quiet);
    border-radius: var(--c-radius-full);
    font-size: 0.9em;
  }

  .badge__prefix {
    padding-inline: calc(var(--c-spacing-md) / 2);
  }

  .badge__suffix {
    padding-inline: calc(var(--c-spacing-md) / 2);
  }
`;
