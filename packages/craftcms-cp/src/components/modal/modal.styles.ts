import {css} from 'lit';

export default css`
  ::slotted([slot='content']) {
    display: block;
    background-color: white;
    border-radius: var(--c-modal-radius);
    border-width: var(--c-modal-border-width);
    border-style: var(--c-modal-border-style);
    border-color: var(--c-modal-border-color);
  }

  .overlays__backdrop {
    background-color: red;
  }
`;
