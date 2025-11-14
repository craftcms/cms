import {css} from 'lit';
import {baseFieldStyles, baseInputStyles} from '../../styles/form.styles';

export default css`
  ${baseFieldStyles}

  :host {
    width: 100%;
  }

  ::slotted(select) {
    width: 100%;
    height: 100%;
    appearance: none;
    border: 0;
    padding-inline: var(--c-input-spacing-inline);
    border-radius: var(--c-input-radius);
  }

  .input-group__input {
    ${baseInputStyles}
    padding-inline: 0;
    position: relative;
  }

  .indicator {
    position: absolute;
    inset-block-start: 50%;
    inset-inline-end: var(--c-input-spacing-inline);
    transform: translateY(-50%);
  }
`;
