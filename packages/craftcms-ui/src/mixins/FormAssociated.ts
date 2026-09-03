import type {LitElement, PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** The form-control surface the mixin adds. */
export declare class FormAssociatedHost {
  /**
   * The value to submit, or `null` to submit nothing. Hosts override this;
   * the default posts nothing.
   *
   * Underscore-prefixed because it is the contract between a component and
   * this mixin rather than public API, so the manifest drops it.
   */
  _formValue(): string | null;

  /**
   * Applies a value the browser handed back — on `form.reset()`, or when it
   * restores a session. Receives exactly what `_formValue()` produced. Hosts
   * override this; the default does nothing.
   */
  _restoreFormValue(value: string | null): void;

  /** Field name. The control posts under this, like any other input. */
  name: string;

  /**
   * Whether the control is disabled. A disabled control does not respond to
   * input and is left out of form submission.
   */
  disabled: boolean;

  /** The form this control belongs to, or `null` when it is outside one. */
  readonly form: HTMLFormElement | null;

  /** The control's current validity state. */
  readonly validity: ValidityState;

  /** The message shown when the control is invalid. Empty when it is valid. */
  readonly validationMessage: string;

  /** Whether the control takes part in constraint validation. */
  readonly willValidate: boolean;

  /** Reports whether the control is valid, without showing anything. */
  checkValidity(): boolean;

  /** Reports whether the control is valid, showing the message if it is not. */
  reportValidity(): boolean;
}

/**
 * Makes a Lit element a form-associated custom element: it posts with
 * `<form>`, `new FormData(form)` reads it, `form.reset()` resets it, and
 * `form.checkValidity()` includes it — the same deal a native input gets, with
 * no hidden input standing in for the value.
 *
 * The host says what its value is and how to put one back; everything else —
 * `name`, `disabled`, the validity surface, and the four lifecycle callbacks —
 * comes from here:
 *
 * ```ts
 * class CraftThing extends FormAssociated(LitElement) {
 *   @property({type: Number}) value = 0;
 *
 *   _formValue() {
 *     return String(this.value);
 *   }
 *
 *   _restoreFormValue(value: string | null) {
 *     this.value = Number(value ?? 0);
 *   }
 * }
 * ```
 *
 * The value is re-posted after every render, so a host has nothing to call
 * when its value changes — but a host that overrides `updated()` must chain to
 * `super.updated()`, or the value stops being posted. What a host must still
 * do is fire the events: native `input` on every alteration and native
 * `change` on commit, matching the control it behaves like.
 *
 * The analyzer does not follow members through a mixin, so a host documents
 * the two it gains with `@attr name` and `@attr disabled` on its own class
 * docblock, the way `craft-pane` documents `Paddable`'s `padding`.
 */
export const FormAssociated = <T extends Constructor<LitElement>>(Base: T) => {
  class FormAssociatedElement extends Base {
    static formAssociated = true;

    #internals = this.attachInternals();

    /** The value to restore on `form.reset()`, read once on first connect. */
    #defaultValue: string | null = null;
    #defaultValueRead = false;

    @property({reflect: true}) name = '';
    @property({type: Boolean, reflect: true}) disabled = false;

    _formValue(): string | null {
      return null;
    }

    // oxlint-disable-next-line no-unused-vars
    _restoreFormValue(value: string | null): void {}

    get form(): HTMLFormElement | null {
      return this.#internals.form;
    }

    get validity(): ValidityState {
      return this.#internals.validity;
    }

    get validationMessage(): string {
      return this.#internals.validationMessage;
    }

    get willValidate(): boolean {
      return this.#internals.willValidate;
    }

    checkValidity(): boolean {
      return this.#internals.checkValidity();
    }

    reportValidity(): boolean {
      return this.#internals.reportValidity();
    }

    override connectedCallback() {
      super.connectedCallback();

      // First connect only: a reconnect must not adopt the current value as
      // the one a reset goes back to.
      if (!this.#defaultValueRead) {
        this.#defaultValueRead = true;
        this.#defaultValue = this._formValue();
      }

      this.#internals.setFormValue(this._formValue());
    }

    protected override updated(changed: PropertyValues) {
      super.updated(changed);
      this.#internals.setFormValue(this._formValue());
    }

    /** Called when an ancestor `<fieldset disabled>` is toggled. */
    formDisabledCallback(disabled: boolean) {
      this.disabled = disabled;
    }

    formResetCallback() {
      this._restoreFormValue(this.#defaultValue);
    }

    formStateRestoreCallback(state: string) {
      this._restoreFormValue(state);
    }
  }

  // Cast through named types only, so Lit's protected members stay out of
  // consumers' declaration emit (TS4094) — same reason as `Paddable`.
  return FormAssociatedElement as unknown as Constructor<FormAssociatedHost> &
    T;
};
