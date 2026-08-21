import {css} from 'lit';

export default css`
  :host {
    position: relative;
    display: block;
  }

  /* A slotted control with a maxlength (reflected as has-maxlength) shrinks
     the field to the control's width instead of spanning the column.
     width="full" spans despite a maxlength; width="auto" shrinks without
     one. */
  :host([has-maxlength]:not([width='full'])),
  :host([width='auto']) {
    width: fit-content;
  }

  /* Stop the input chrome from flexing back out to the available space. */
  :host([has-maxlength]:not([width='full'])) .input-group__input,
  :host([width='auto']) .input-group__input {
    flex: 0 0 auto;
  }

  :host([hidden]) {
    display: none;
  }

  .form-field {
    padding-inline: var(--c-spacing-lg);
  }

  .form-field__status-indicator {
    position: absolute;
    inset-block-start: 0;
    inset-inline-start: 0;
    width: 2px;
    height: 100%;
    cursor: help;
    z-index: 1;
    border-radius: 1px;
  }

  .form-field--modified .form-field__status-indicator {
    background-color: var(--blue-600, #2563eb);
    box-shadow: 0 0 5px hsl(221deg 83% 53% / 15%);
  }

  .form-field--outdated .form-field__status-indicator {
    background-color: var(--bg-pending, #c96a11);
    box-shadow: 0 0 5px hsl(27deg 96% 61% / 15%);
  }

  /* Heading row (the CP's .field > .heading) */
  .form-field__label {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    align-items: center;
    font-weight: bold;
    margin-block-end: var(--c-spacing-xs, 0.25rem);
  }

  /* Pushes slotted label extras to the far end of the heading row. */
  .form-field__label .flex-grow {
    flex: 1 0 0;
  }

  .field-actions {
    display: flex;
    flex-wrap: nowrap;
    gap: var(--c-spacing-2xs, 0.125rem);
    align-items: center;
  }

  ::slotted([slot='label']) {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-xs);
    align-items: center;
  }

  .read-only-badge {
    font-size: calc(11rem / 16);
    font-weight: normal;
    line-height: 1.45;
    padding-block: 0;
    padding-inline: 0.25em;
    background-color: var(--gray-100, #e9ebec);
    color: var(--gray-700, #3f4d5a);
    border: 1px solid var(--border-hairline, hsl(211deg 20% 44% / 25%));
    border-radius: var(--c-radius-sm, 3px);
  }

  /* Instructions (.field > .instructions in the CP) */
  .form-field__help-text {
    display: block;
    margin-block-end: var(--c-spacing-xs, 0.3125rem);
  }

  .form-field__group-two .form-field__help-text {
    margin-block: var(--c-spacing-xs, 0.3125rem) 0;
  }

  /* Input container (.field > .input in the CP) */
  .input-group {
    position: relative;
  }

  .input-group.ltr {
    direction: ltr;
  }

  .input-group.rtl {
    direction: rtl;
  }

  .input-group.disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .input-group__container {
    display: flex;
  }

  .input-group__input {
    flex: 1;
    display: flex;
    /* Let the chrome shrink below its content's intrinsic width (e.g. a
       slotted input with a large size attribute), instead of blowing out
       of the column. */
    min-width: 0;
  }

  .input-group__container > .input-group__input ::slotted(.form-control) {
    flex: 1 1 auto;
    margin: 0;
    font-size: 100%;
  }

  /* Tip/warning notices (field-notice.blade.php renders these with p-0 mt-1) */
  .field-notice {
    padding: 0;
    margin-block-start: var(--c-spacing-xs, 0.25rem);
  }

  /* Error styling hook, mirroring .field.has-errors / .input.errors */
  :host([has-errors]) ::slotted([slot='input']) {
    border-color: var(--c-color-danger-border-loud);
  }

  :host([has-errors]) .form-field__feedback {
    color: var(--c-color-danger-on-normal);
  }
`;
