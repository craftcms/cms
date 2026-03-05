import {css} from 'lit';

export default css`
  /* If an input has a "maxlength" attribute, it should not grow */
  :host([maxlength]) {
    .input-group__container {
      display: inline-flex;
      width: auto;
    }

    ::slotted(.form-control) {
      width: auto;
      flex: 0 0 auto;
    }
  }

  craft-input input[type='checkbox'],
  craft-input input[type='radio'] {
    background-color: var(--c-input-fill, var(--c-form-control-fill));
    border-width: var(
      --c-input-border-width,
      var(--c-form-control-border-width)
    );
    border-style: var(
      --c-input-border-style,
      var(--c-form-control-border-style)
    );
    border-color: var(
      --c-input-border-color,
      var(--c-form-control-border-color)
    );
    border-radius: var(--c-input-radius, var(--c-radius-sm));
  }

  [slot='help-text'] {
    font-size: var(--c-text-base);
    color: var(--c-text-quiet);
  }
`;
