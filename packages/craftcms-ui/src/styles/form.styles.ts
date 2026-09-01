import {css} from 'lit';

/**
 * Base styles for the form control itself — the native `<input>`/`<select>`/
 * `<textarea>` (or equivalent custom element, e.g. `craft-select-invoker`)
 * that actually receives focus and holds the value. Apply this to the
 * focusable control, not its wrapper.
 *
 * It sets the control's own border, radius, background, box-shadow, and
 * min-height, so the control's `:focus-visible` outline traces its own shape
 * rather than an ancestor's. Pair it with {@link baseInputWrapperStyles} on
 * the surrounding `.input-group__input`/`.input-group__container` div.
 */
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
  border-radius: var(--_input-start-start-radius) var(--_input-start-end-radius)
    var(--_input-end-end-radius) var(--_input-end-start-radius);
  background-color: var(--c-input-fill, var(--c-form-control-fill));
  box-shadow: var(--c-input-shadow);
  min-height: calc(
    var(--c-input-height, var(--c-size-control-md)) - 2 *
      var(--_input-border-width)
  );
`;

/**
 * Base styles for the `.input-group__input`/`.input-group__container` div
 * that Lion's `FormControlMixin` renders around a slotted form control (see
 * `_inputGroupInputTemplate()` in `@lion/ui`'s `form-core`). Apply this to
 * that wrapper element, not the control itself.
 *
 * It only sets layout/sizing concerns the wrapper needs (flex, width,
 * position) — it does not draw a border, radius, or background of its own.
 * Pair it with {@link baseFormControlStyles} on the actual
 * `::slotted([slot='input'])`/`::slotted(.form-control)` control.
 */
export const baseInputWrapperStyles = css`
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

/**
 * @deprecated Use {@link baseFormControlStyles} on the control and
 * {@link baseInputWrapperStyles} on its wrapper instead. This combined mixin
 * predates that split and is kept only so existing imports of
 * `baseInputStyles` keep working — it composes the two split mixins, so it
 * still picks up fixes made to either of them (e.g. the removed
 * `overflow: clip` that was clipping focus outlines), but applying one mixin
 * to two different elements no longer reflects how the styles are actually
 * structured. Migrate to the split versions when you can.
 */
export const baseInputStyles = css`
  ${baseFormControlStyles}
  ${baseInputWrapperStyles}
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
    ${baseInputWrapperStyles}
  }

  .input-group__prefix,
  .input-group__suffix {
    padding-inline: var(--c-input-spacing-inline);
    display: grid;
    place-items: center;
    border: var(--c-input-border-width) var(--c-input-border-style)
      var(--c-input-border-color);
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

/**
 * The native input a choice control (checkbox, indeterminate "all" checkbox)
 * slots into its light DOM. Shared so every choice renders the same box —
 * without it a control falls back to the browser's default checkbox, which is
 * a visibly different size from its siblings.
 */
export const choiceInputStyles = css`
  ::slotted([slot='input']) {
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
    width: var(--c-checkbox-size);
    height: var(--c-checkbox-size);
  }
`;
