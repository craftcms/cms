import {css} from 'lit';

export const baseInputStyles = css`
  font: inherit;
  color: var(--c-input-fg, var(--c-fg-text));
  position: relative;
  min-height: var(--c-input-height, var(--c-size-control-md));
  border: var(--c-input-border, 1px solid var(--c-form-control-border));
  border-radius: var(--c-input-radius, var(--c-radius-sm));
  padding-inline: var(--c-input-spacing-inline, var(--c-spacing-md));
  background-color: var(--c-input-bg, var(--c-form-control-bg));
  box-shadow: var(--c-input-shadow);
`;

export const inputStyles = css`
  :host(:not([label-sr-only])) .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
  }

  ::slotted(label) {
    line-height: 1;
    font-weight: bold;
    font-size: var(--text-sm);
  }

  ::slotted([slot='input']) {
    ${baseInputStyles}
  }

  /* Detect mobile devices and up the font size of inputs to avoid zoom on focus */
  @media (pointer: none), (pointer: coarse) {
    ::slotted([slot='input']) {
      font-size: 1rem;
    }
  }

  .form-field__help-text {
    font-size: 1em;
    color: var(--c-fg-muted);
  }

  .form-field__group-one {
    margin-bottom: var(--c-spacing-sm);
  }
`;
