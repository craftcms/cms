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
    padding-block: var(--_spacing-block);
    line-height: var(--leading-normal);
    resize: vertical;
  }

  :host {
    --_spacing-block: var(--c-textarea-spacing-block, var(--c-spacing-md));
  }

  @supports (field-sizing: content) {
    /* Matches the selector that makes the textarea content-box. */
    .input-group__container > .input-group__input ::slotted([slot='input']) {
      box-sizing: border-box;
      --_block-offset: calc(
        2 * (var(--_spacing-block, 0px) + var(--_input-border-width, 0px))
      );
      field-sizing: content;
      min-block-size: calc(var(--_rows) * 1lh + var(--_block-offset));
      max-block-size: calc(var(--_max-rows) * 1lh + var(--_block-offset));
      /* Scrolls once it stops growing, even where the page hides overflow. */
      overflow-y: auto !important;
    }
  }

  .input-group__input:has(> .chars-left) {
    ${baseFormControlStyles}
    flex-direction: column;
    min-height: 0;
  }

  .input-group__container
    > .input-group__input:has(> .chars-left)
    ::slotted([slot='input']) {
    --c-focus-outline-width: 0px;
    border: none;
    border-radius: inherit;
    background-color: transparent;
    box-shadow: none;
    padding-inline-end: var(--c-input-spacing-inline);
  }

  :host([focused-visible]) .input-group__input:has(> .chars-left) {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
  }

  :host([invalid]) .input-group__input:has(> .chars-left) {
    border-color: var(--c-color-danger-border-loud);
  }

  .input-group__input > .chars-left {
    position: static;
    align-self: flex-end;
    block-size: auto;
    padding-inline: var(--c-input-spacing-inline);
    padding-block-end: var(--_spacing-block);
    line-height: 1;
  }
`;
