import {css} from 'lit';
import {
  baseFieldStyles,
  baseFormControlStyles,
  baseInputWrapperStyles,
  baseComboboxStyles,
} from '../../styles/form.styles';

export default css`
  ${baseFieldStyles}

  :host {
    width: 100%;
  }

  ::slotted(.form-control) {
    ${baseComboboxStyles}
  }

  ::slotted([slot='listbox']) {
    display: grid;
    gap: var(--c-spacing-xs);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-overlay);
    box-shadow: var(--c-shadow-sm);
    padding: var(--c-spacing-sm);
  }

  .input-group__input {
    ${baseInputWrapperStyles}
  }

  .input-group__container {
    border: 0;
  }

  .indicator {
    position: absolute;
    inset-block-start: 50%;
    inset-inline-end: var(--c-input-spacing-inline);
    transform: translateY(-50%);
    width: 1em;
    height: 1em;
  }

  .clear {
    position: absolute;
    inset-block-start: 50%;
    inset-inline-end: calc(var(--c-input-spacing-inline) * 1.5 + 1em);
    transform: translateY(-50%);
  }

  .prefix {
    position: absolute;
    inset-block-start: 50%;
    inset-inline-start: var(--c-input-spacing-inline);
    transform: translateY(-50%);
    pointer-events: none;
  }

  :host([has-prefix-icon]) ::slotted(.form-control) {
    padding-inline-start: calc(var(--c-input-spacing-inline) * 1.5 + 1em);
  }

  :host([multiple-choice]) .input-group__input {
    ${baseFormControlStyles}
    box-sizing: border-box;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs);
    padding: var(--c-spacing-xs);
  }

  :host([multiple-choice]) .input-group__input:focus-within {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
  }

  :host([multiple-choice][has-feedback-for='error']) .input-group__input {
    border-color: var(--c-color-danger-border-loud);
  }

  .combobox__textbox {
    position: relative;
    flex: 1;
    min-width: 0;
  }

  :host([multiple-choice]) .combobox__textbox {
    min-width: 8rem;
  }

  :host([multiple-choice]) ::slotted(.form-control) {
    height: calc(
      var(--c-input-height, var(--c-size-control-md)) - 2 *
        (var(--c-spacing-xs) + var(--_input-border-width))
    );
    min-height: 0;
    padding-inline-start: 0;
    border: 0;
    background: transparent;
    box-shadow: none;
    outline: none;
  }

  .token {
    box-sizing: border-box;
    max-width: 100%;
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    padding: var(--c-spacing-xs);
    border-radius: var(--c-radius-sm);
    background: var(--c-color-neutral-fill-quiet);
    overflow-wrap: anywhere;
  }

  .token button {
    border: 0;
    background: transparent;
    font: inherit;
    color: inherit;
    cursor: pointer;
  }

  .combobox__option {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .combobox__optgroup {
    padding-inline: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    font-size: 0.8em;
    text-transform: uppercase;
    color: var(--c-color-neutral-on-normal);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .combobox__footer {
    padding-inline: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    font-size: 0.85em;
    color: var(--c-text-quiet);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }
`;
