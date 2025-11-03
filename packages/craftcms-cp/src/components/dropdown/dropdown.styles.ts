import {css} from 'lit';

export default css`
  :host {
    display: contents;
  }

  .panel {
    display: grid;
    gap: 1px;
    flex-direction: column;
    width: max-content;
    position: absolute;
    padding: var(--spacing-sm);
    background-color: var(--bg-overlay);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-sm);
  }
`;
