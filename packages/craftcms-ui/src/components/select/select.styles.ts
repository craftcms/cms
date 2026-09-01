import {css} from 'lit';
import {
  baseFieldStyles,
  baseInputWrapperStyles,
  baseComboboxStyles,
} from '@src/styles/form.styles';

export default css`
  ${baseFieldStyles}

  :host {
    width: 100%;
  }

  :host([small]) .input-group__input {
    --c-input-height: calc(var(--c-size-control-sm) - 2px);
  }

  ::slotted(.form-control) {
    ${baseComboboxStyles}
  }

  .input-group__input {
    ${baseInputWrapperStyles}
  }

  .indicator {
    position: absolute;
    inset-block-start: 50%;
    inset-inline-end: var(--c-input-spacing-inline);
    transform: translateY(-50%);
    width: 1em;
    height: 1em;
  }
`;
