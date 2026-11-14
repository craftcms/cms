import {css} from 'lit';
import {baseFormControlStyles} from '@src/styles/form.styles';

export default css`
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
    resize: vertical;
  }

  @supports (field-sizing: content) {
    /* Matches the selector that makes the textarea content-box. */
    .input-group__container > .input-group__input ::slotted([slot='input']) {
      box-sizing: border-box;
      --_block-offset: calc(
        2 * (var(--c-spacing-md, 0px) + var(--_input-border-width, 0px))
      );
      field-sizing: content;
      min-block-size: calc(var(--_rows) * 1lh + var(--_block-offset));
      max-block-size: calc(var(--_max-rows) * 1lh + var(--_block-offset));
    }
  }
`;
