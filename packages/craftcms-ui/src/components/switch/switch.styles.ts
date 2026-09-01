import {css} from 'lit';
export default css`
  :host {
    display: grid;
  }

  .input-group {
    display: inline-flex;
    align-items: center;
  }

  ::slotted(label) {
    font-weight: bold;
  }

  .state-label {
    cursor: pointer;
    user-select: none;
  }

  .state-label[data-state='off'] {
    margin-inline-end: var(--c-spacing-sm);
  }

  .state-label[data-state='on'] {
    margin-inline-start: var(--c-spacing-sm);
  }

  ::slotted([slot='state-description']) {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    clip-path: inset(50%);
    white-space: nowrap;
  }
`;
