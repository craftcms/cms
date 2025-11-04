import {css} from 'lit';
export default css`
  :host {
    width: 100%;
  }

  ::slotted(label) {
    font-weight: bold;
  }

  .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
  }

  .form-field__help-text {
    font-size: var(--text-sm);
    color: var(--color-slate-600);
  }

  #overlay-content-node-wrapper {
    background-color: canvas;
    padding: var(--c-spacing-sm);
    border: 1px solid var(--c-color-neutral-border-subtle);
    border-radius: var(--c-radius-md);
  }
`;
