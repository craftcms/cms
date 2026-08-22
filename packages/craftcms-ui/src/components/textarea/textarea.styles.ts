import {css} from 'lit';
import {baseFormControlStyles} from '@src/styles/form.styles';

export default css`
  :host(:not([label-sr-only])) .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
  }

  :host([monospace]) ::slotted([slot='input']) {
    font-family: var(--c-font-mono, monospace) !important;
    font-size: var(--c-text-sm);
  }

  ::slotted(label) {
    font-weight: bold;
  }

  ::slotted([slot='input']) {
    ${baseFormControlStyles}
    padding-block: var(--c-spacing-md);
    line-height: var(--leading-normal);
    resize: none;
  }
`;
