import {css} from 'lit';

/*
 * Note: the structural styling for the option wrapper (`.select-color__option`),
 * swatch (`.select-color__swatch`), and label (`.select-color__label`) lives
 * inline in `select-color.ts`. Those nodes get cloned by Lion's
 * `LionSelectInvoker` into the invoker's shadow root, where this component's
 * scoped class rules don't apply, so they carry their own inline styles to stay
 * self-contained. The class names are retained for testing/targeting only.
 */
export default css`
  :host {
    display: block;
  }
`;
