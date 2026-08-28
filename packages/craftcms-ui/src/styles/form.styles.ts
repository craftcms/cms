import {css} from 'lit';

export const baseInputStyles = css`
  --_border-width: var(
    --c-input-border-width,
    var(--c-form-control-border-width)
  );
  font: inherit;
  color: var(--c-input-text, var(--c-text-default));
  position: relative;
  min-height: calc(
    var(--c-input-height, var(--c-size-control-md)) - 2 * var(--_border-width)
  );
  border-width: var(--_border-width);
  border-style: var(--c-input-border-style, var(--c-form-control-border-style));
  border-color: var(--c-input-border-color, var(--c-form-control-border-color));
  border-radius: var(--c-input-radius, var(--c-radius-sm));
  padding-block: 0;
  width: 100%;
  flex: 1 1 auto;
  background-color: var(--c-input-fill, var(--c-form-control-fill));
  box-shadow: var(--c-input-shadow);
  overflow: clip;

  /* Detect mobile devices and up the font size of inputs to avoid zoom on focus */
  @media (pointer: none), (pointer: coarse) {
    font-size: 1rem;
  }
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
    font-weight: var(--c-field-label-font-weight, bold);
    font-size: var(--c-field-label-font-size, var(--text-sm));
  }

  .form-field__help-text {
    font-size: 1em;
    color: var(--c-text-quiet);
  }

  ::slotted([slot='after']) {
    margin-block-start: var(--c-spacing-sm);
  }

  /* label-position: start — render the label beside the control instead of
     stacked above it. Guarded by :not([has-help-text]) — a component sets
     that attribute itself whenever help text/instructions are present, so
     the combination automatically falls back to the normal stacked layout
     instead of producing a broken hybrid.

     The negation lives INSIDE the :host() argument
     (:host([a]:not([b]))), matching the :host(:not([label-sr-only]))
     pattern above — chaining :not() AFTER :host(...) instead
     (:host([a]):not([b])) does not reliably match. */
  :host([label-position='start']:not([has-help-text])) {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  :host([label-position='start']:not([has-help-text]))
    .form-field__group-one {
    flex: 0 0 auto;
  }

  :host([label-position='start']:not([has-help-text]))
    .form-field__group-one
    .form-field__label
    slot:not(:empty) {
    margin-block-end: 0; /* the :host gap handles spacing instead */
  }

  :host([label-position='start']:not([has-help-text])) ::slotted(label) {
    font-weight: var(--c-field-label-font-weight-inline, normal);
    font-size: var(--c-field-label-font-size-inline, inherit);
    line-height: inherit;
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
