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

  craft-input,
  craft-input-color,
  craft-icon-picker {
    inline-size: 100%;
  }

  .actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }
`;
