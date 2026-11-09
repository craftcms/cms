import {html, nothing, render, type LitElement, type PropertyValues} from 'lit';
import {property, state} from 'lit/decorators.js';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** What a control posts: one value, a list of them, or nothing. */
export type FormValue = string | string[] | null;

/** The form-control surface the mixin adds. */
export declare class FormAssociatedHost {
  /**
   * The value to submit, or `null` to submit nothing. A list posts as
   * `name[]`, after an empty `name` so an empty list still posts. Hosts
   * override this; the default posts nothing.
   *
   * Underscore-prefixed because it is the contract between a component and
   * this mixin rather than public API, so the manifest drops it.
   */
  _formValue(): FormValue;

  /**
   * Applies the value `form.reset()` goes back to: what `_formValue()`
   * returned on first connect. Hosts override this; the default does nothing.
   */
  _restoreFormValue(value: FormValue): void;

  /**
   * Writes the value to the hidden inputs. Runs after every update; call it
   * when the value changes outside of one.
   */
  protected _syncFormValue(): void;

  /** Sets the control's validity, as `ElementInternals.setValidity()` does. */
  protected _setValidity(
    flags?: ValidityStateFlags,
    message?: string,
    anchor?: HTMLElement
  ): void;

  /** Field name. The control posts under this, like any other input. */
  name: string;

  /**
   * Whether the control is disabled. A disabled control does not respond to
   * input and is left out of form submission.
   */
  disabled: boolean;

  /**
   * Whether the browser counts the control disabled, through its own
   * `disabled` or an ancestor `<fieldset disabled>`. Kept apart from
   * `disabled`, which holds only what was set on the control, so re-enabling
   * the fieldset enables the control again. Hosts check
   * `disabled || _formDisabled` to decide whether the control responds.
   */
  protected readonly _formDisabled: boolean;

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
 * `form.checkValidity()` includes it — the same deal a native input gets.
 *
 * The value posts through hidden inputs in the host's light DOM rather than
 * `ElementInternals`, so code that serializes a form's native controls, such
 * as jQuery's `serialize()`, sees it too.
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
 *   _restoreFormValue(value: FormValue) {
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

    // Test DOMs such as happy-dom lack ElementInternals, or parts of it.
    #internals: Partial<ElementInternals> | undefined =
      typeof this.attachInternals === 'function'
        ? this.attachInternals()
        : undefined;

    /** The value to restore on `form.reset()`, read once on first connect. */
    #defaultValue: FormValue = null;
    #defaultValueRead = false;

    /** Holds the hidden inputs, in a slot the shadow root doesn't render. */
    #inputs?: HTMLElement;

    /** What the hidden inputs last posted, so an unchanged value is skipped. */
    #posted?: string;

    @property({reflect: true}) name = '';
    @property({type: Boolean, reflect: true}) disabled = false;

    @state() protected _formDisabled = false;

    _formValue(): FormValue {
      return null;
    }

    // oxlint-disable-next-line no-unused-vars
    _restoreFormValue(value: FormValue): void {}

    protected _setValidity(
      flags: ValidityStateFlags = {},
      message = '',
      anchor?: HTMLElement
    ): void {
      this.#internals?.setValidity?.(flags, message, anchor);
    }

    get form(): HTMLFormElement | null {
      return this.#internals?.form ?? null;
    }

    get validity(): ValidityState {
      return this.#internals?.validity ?? ({valid: true} as ValidityState);
    }

    get validationMessage(): string {
      return this.#internals?.validationMessage ?? '';
    }

    get willValidate(): boolean {
      return this.#internals?.willValidate ?? false;
    }

    checkValidity(): boolean {
      return this.#internals?.checkValidity?.() ?? true;
    }

    reportValidity(): boolean {
      return this.#internals?.reportValidity?.() ?? true;
    }

    override connectedCallback() {
      super.connectedCallback();

      // First connect only: a reconnect must not adopt the current value as
      // the one a reset goes back to.
      if (!this.#defaultValueRead) {
        this.#defaultValueRead = true;
        this.#defaultValue = this._formValue();
      }

      this._syncFormValue();
    }

    protected override updated(changed: PropertyValues) {
      super.updated(changed);
      this._syncFormValue();
    }

    /**
     * Writes the value to the hidden inputs. Runs after every update; call it
     * when the value changes outside of one.
     */
    protected _syncFormValue(): void {
      const value = this.name && !this.disabled ? this._formValue() : null;

      const posted = JSON.stringify([this.name, value]);

      if (
        (value === null && !this.#inputs) ||
        (posted === this.#posted && this.#inputs?.parentNode === this)
      ) {
        return;
      }

      this.#posted = posted;

      if (!this.#inputs) {
        this.#inputs = document.createElement('span');
        this.#inputs.hidden = true;
        this.#inputs.slot = 'form-value';
      }

      if (this.#inputs.parentNode !== this) {
        this.append(this.#inputs);
      }

      render(
        Array.isArray(value)
          ? html`<input type="hidden" name=${this.name} value="" />${value.map(
                (item) =>
                  html`<input
                    type="hidden"
                    name=${`${this.name}[]`}
                    .value=${item}
                  />`
              )}`
          : value === null
            ? nothing
            : html`<input type="hidden" name=${this.name} .value=${value} />`,
        this.#inputs
      );
    }

    /** Called when an ancestor `<fieldset disabled>` is toggled. */
    formDisabledCallback(disabled: boolean) {
      this._formDisabled = disabled;
    }

    formResetCallback() {
      this._restoreFormValue(this.#defaultValue);
    }
  }

  // Cast through named types only, so Lit's protected members stay out of
  // consumers' declaration emit (TS4094) — same reason as `Paddable`.
  return FormAssociatedElement as unknown as Constructor<FormAssociatedHost> &
    T;
};
