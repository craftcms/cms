import {css} from 'lit';

export default css`
  :host {
    display: block;
  }

  [data-object-select-row] {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  [data-object-select-controls] {
    display: flex;
    gap: var(--c-spacing-sm);
    margin-block-start: var(--c-spacing-sm);
  }
`;
