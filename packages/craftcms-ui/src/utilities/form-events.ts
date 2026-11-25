/**
 * The two native events a form control owes its consumers, dispatched from the
 * host so `event.target` is the component rather than whatever input it wraps.
 *
 * Both are composed, so they cross a shadow boundary the way the platform's own
 * `input` does, and a form-level listener sees them from anywhere in the tree.
 */

/**
 * Emitted on every alteration to the value, while it is still being made.
 * Given an `inputType`, it's an `InputEvent` carrying it, as a native input's
 * is: `insertReplacementText` marks a value picked rather than typed.
 */
export const emitInput = (host: HTMLElement, inputType?: string): void => {
  host.dispatchEvent(
    inputType === undefined
      ? new Event('input', {bubbles: true, composed: true})
      : new InputEvent('input', {bubbles: true, composed: true, inputType})
  );
};

/**
 * Emitted when an alteration is committed — a selection, a blur after typing.
 * For a control whose every step is a commit, it follows each `emitInput`.
 */
export const emitChange = (host: HTMLElement): void => {
  host.dispatchEvent(new Event('change', {bubbles: true, composed: true}));
};
