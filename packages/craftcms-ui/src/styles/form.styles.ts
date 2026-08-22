import {css} from 'lit';

export const baseFormControlStyles = css`
  --_input-border-width: var(
      --c-input-border-width,
      var(--c-form-control-border-width)
  );
  --_input-start-start-radius: var(--c-input-radius, var(--c-radius-sm));
  --_input-start-end-radius: var(--c-input-radius, var(--c-radius-sm));
  --_input-end-start-radius: var(--c-input-radius, var(--c-radius-sm));
  --_input-end-end-radius: var(--c-input-radius, var(--c-radius-sm));
  border-width: var(--_input-border-width);
  border-style: var(--c-input-border-style, var(--c-form-control-border-style));
  border-color: var(--c-input-border-color, var(--c-form-control-border-color));
  border-radius: var(--_input-start-start-radius) var(--_input-start-end-radius) var(--_input-end-end-radius) var(--_input-end-start-radius);
  background-color: var(--c-input-fill, var(--c-form-control-fill));
  box-shadow: var(--c-input-shadow);
  min-height: calc(
      var(--c-input-height, var(--c-size-control-md)) - 2 * var(--_input-border-width)
  );
`;

/** Wrapper div **/
export const baseInputStyles = css`
  font: inherit;
  color: var(--c-input-text, var(--c-text-default));
  position: relative;
  padding-block: 0;
  width: 100%;
  flex: 1 1 auto;

  /* Detect mobile devices and up the font size of inputs to avoid zoom on focus */
  @media (pointer: none), (pointer: coarse) {
    font-size: 1rem;
  }
`;

export const baseComboboxStyles = css`
  ${baseFormControlStyles}
  width: 100%;
  height: 100%;
  min-height: none;
  appearance: none;
  padding-inline: var(--c-input-spacing-inline)
    calc(var(--c-input-spacing-inline) * 1.5 + 1em);
`;

export const baseFieldStyles = css`
  :host(:not([label-sr-only]))
    .form-field__group-one
    .form-field__label
    slot:not(:empty) {
    margin-block-end: var(--c-spacing-sm);
  }

  :host([has-feedback-for='error']) {
    color: var(--c-color-danger-on-normal);

    ::slotted([slot='input']) {
      border-color: var(--c-color-danger-border-loud);
    }
  }

  ::slotted(label) {
    line-height: 1;
    font-weight: bold;
    font-size: var(--text-sm);
  }

  .form-field__help-text {
    font-size: 1em;
    color: var(--c-text-quiet);
  }

  ::slotted([slot='after']) {
    margin-block-start: var(--c-spacing-sm);
  }
`;

export const inputStyles = css`
  ${baseFieldStyles}

  :host([monospace]) .input-group__container {
    font-family: var(--c-font-mono);
    font-size: 0.9em;
  }

  ::slotted([slot='input']) {
    font: inherit;
    padding-block: 0;
    border: none;
    border-radius: var(--c-input-radius, var(--c-radius-sm));
    appearance: none;
    padding-inline: var(--c-input-spacing-inline);
    background-color: transparent;
    width: 100%;
  }

  .input-group__container {
    ${baseInputStyles}
  }

  .input-group__prefix,
  .input-group__suffix {
    padding-inline: var(--c-input-spacing-inline);
    display: grid;
    place-items: center;
    border: var(--c-input-border-width) var(--c-input-border-style) var(--c-input-border-color);
    border-radius: var(--c-input-radius, var(--c-radius-sm));
  }
  
  .input-group__prefix {
    border-inline-end: 0;
    border-end-end-radius: 0;
    border-start-end-radius: 0;
  }
  
  .input-group__suffix {
    border-inline-start: 0;
    border-start-start-radius: 0;
    border-end-start-radius: 0;
  }

  .input-group__prefix + .input-group__input ::slotted([slot='input']) {
    --_input-start-start-radius: 0;
    --_input-end-start-radius: 0;
  }

  .input-group__prefix + .input-group__input ::slotted([slot='input']),
  .input-group__input:has(+ .input-group__suffix) ::slotted([slot='input']) {
    --c-focus-outline-style: inset;
    --c-focus-outline-offset: -2px;
  }

  .input-group__input:has(+ .input-group__suffix) ::slotted([slot='input']) {
    --_input-end-end-radius: 0;
    --_input-start-end-radius: 0;
  }

  .input-group__prefix + .input-group__input {
    border-radius-start-start: 0;
    border-radius-start-end: 0;
  }

  :host([size~='small']) ::slotted([slot='input']) {
    --c-input-height: var(--c-size-control-sm);
    --c-input-spacing-inline: var(--c-spacing-sm);
  }

  :host([center]) ::slotted([slot='input']) {
    text-align: center;
  }

  ::slotted([slot='input']) {
    width: 100%;
  }
`;
