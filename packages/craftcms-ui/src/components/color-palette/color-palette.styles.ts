import {css} from 'lit';

export default css`
  :host {
    display: block;
  }

  table {
    border-collapse: collapse;
    inline-size: 100%;
    margin-block-end: var(--c-spacing-sm);
  }

  th,
  td {
    padding: var(--c-spacing-xs);
    text-align: start;
    vertical-align: middle;
  }

  th:last-child,
  td:last-child {
    white-space: nowrap;
  }
`;
