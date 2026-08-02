import {css} from 'lit';

export default css`
  :host {
    display: block;
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th,
  td {
    padding: var(--c-spacing-xs);
    text-align: start;
  }

  textarea {
    box-sizing: border-box;
    width: 100%;
  }

  .actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }
`;
