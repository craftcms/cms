import {css} from 'lit';

export default css`
  craft-button {
    cursor: move;
  }

  :host([disabled]) {
    cursor: default;
    opacity: 0.25;
    pointer-events: none;
  }
`;
