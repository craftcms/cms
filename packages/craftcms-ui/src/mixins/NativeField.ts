import type {LitElement, PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {FormControl, type FormControlInterface} from './FormControl';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

type NativeControl = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;

export declare class NativeFieldInterface extends FormControlInterface {
  get value(): string;
  set value(value: string);
  name?: string;
  placeholder?: string;
  autocomplete?: string;
  required: boolean;
  invalid: boolean;
  get _control(): NativeControl | undefined;
  protected _pushToControl(changed: PropertyValues): void;
}

/**
 * A `FormControl` around a native `<input>`, `<textarea>` or `<select>` in its
 * light DOM, which is what posts with the form. The control's own `input` and
 * `change` events are the component's.
 *
 * Host properties are pushed onto the control when they change. On the first
 * render only the ones that were actually given are, so server-rendered
 * markup keeps its own attributes.
 */
export const NativeField = <T extends Constructor<LitElement>>(Base: T) => {
  class NativeFieldElement extends FormControl(Base) {
    /** The control's `name`. */
    @property({reflect: true}) name?: string;

    /** The control's placeholder. */
    @property() placeholder?: string;

    /** The control's `autocomplete` hint. */
    @property() autocomplete?: string;

    /**
     * Marks the value as required: sets `aria-required` on the control. The
     * server validates it, so the browser's own validation isn't turned on.
     */
    @property({type: Boolean}) required = false;

    /** Whether the control takes focus when it's inserted. */
    @property({type: Boolean}) override autofocus = false;

    /** Marks the value as invalid: sets `aria-invalid` on the control. */
    @property({type: Boolean, reflect: true}) invalid = false;

    /**
     * Held until the host is connected with its control, since a `<select>`
     * rendered in the same template may not have its options yet.
     */
    #pendingValue?: string;

    /** Whether host properties have been pushed onto the control yet. */
    #pushed = false;

    override get _control(): NativeControl | undefined {
      return super._control as NativeControl | undefined;
    }

    /** The control's value. */
    @property({attribute: false})
    get value(): string {
      const control = this._control;

      return control && this.isConnected
        ? control.value
        : (this.#pendingValue ?? control?.value ?? '');
    }

    set value(value: string) {
      const old = this.value;
      const control = this._control;

      if (!control || !this.isConnected) {
        this.#pendingValue = value;
      } else if (control.value !== value) {
        this.#setValueKeepingCaret(control, value);
      }

      this.requestUpdate('value', old);
    }

    #setValueKeepingCaret(control: NativeControl, value: string): void {
      if (
        control.matches(':focus') &&
        !(control instanceof HTMLSelectElement) &&
        control.selectionStart !== null
      ) {
        try {
          const start = control.selectionStart;
          control.value = value;
          control.selectionStart = start;
          control.selectionEnd = start;
          return;
        } catch {
          // Some input types have no selection.
        }
      }

      control.value = value;
    }

    override connectedCallback() {
      super.connectedCallback();

      if (this.#pendingValue !== undefined && this._control) {
        this._control.value = this.#pendingValue;
        this.#pendingValue = undefined;
      }

      this.addEventListener('input', this.#onInput);
      this.addEventListener('focusin', this.#onFocusIn);
      this.addEventListener('focusout', this.#onFocusOut);
      this.#resetRoot = this.getRootNode();
      this.#resetRoot.addEventListener('reset', this.#onReset, true);
    }

    override disconnectedCallback() {
      super.disconnectedCallback();
      this.removeEventListener('input', this.#onInput);
      this.removeEventListener('focusin', this.#onFocusIn);
      this.removeEventListener('focusout', this.#onFocusOut);
      this.#resetRoot?.removeEventListener('reset', this.#onReset, true);
      this.#resetRoot = undefined;
    }

    /** Where the owning form's `reset` is heard, whichever form that is. */
    #resetRoot?: Node;

    /** Re-renders on typing, so anything drawn from `value` follows it. */
    #onInput = (): void => {
      this.requestUpdate('value');
    };

    /**
     * A form reset restores the control's value without an `input` or
     * `change`, and only after `reset` has been dispatched, so anything drawn
     * from `value` is re-rendered once it's done.
     */
    #onReset = (event: Event): void => {
      if (event.target !== this._inputNode?.form) {
        return;
      }

      setTimeout(() => {
        if (!event.defaultPrevented) {
          this.requestUpdate('value');
        }
      });
    };

    #onFocusIn = (event: FocusEvent): void => {
      if (event.target !== this._control) {
        return;
      }

      // Styles keyed to keyboard focus on a slotted control can't read its
      // :focus-visible from the shadow root.
      this.toggleAttribute(
        'focused-visible',
        this._control.matches(':focus-visible')
      );
    };

    #onFocusOut = (event: FocusEvent): void => {
      if (event.target !== this._control) {
        return;
      }

      this.removeAttribute('focused-visible');
    };

    override focus(options?: FocusOptions): void {
      this._control?.focus(options);
    }

    override blur(): void {
      this._control?.blur();
    }

    protected override updated(changed: PropertyValues) {
      super.updated(changed);
      this._pushToControl(changed);
    }

    /**
     * Pushes changed host properties onto the control. Subclasses extend it
     * for the attributes they add.
     */
    protected _pushToControl(changed: PropertyValues): void {
      const control = this._control;

      if (!control) {
        return;
      }

      const first = !this.#pushed;
      this.#pushed = true;
      const given = (key: string, value: unknown) =>
        changed.has(key) &&
        !(first && (value === undefined || value === '' || value === false));

      if (given('name', this.name)) {
        control.name = this.name ?? '';
      }

      if (
        given('placeholder', this.placeholder) &&
        !(control instanceof HTMLSelectElement)
      ) {
        control.placeholder = this.placeholder ?? '';
      }

      if (given('autocomplete', this.autocomplete)) {
        control.setAttribute('autocomplete', this.autocomplete ?? '');
      }

      if (given('required', this.required)) {
        if (this.required) {
          control.setAttribute('aria-required', 'true');
        } else {
          control.removeAttribute('aria-required');
        }
      }

      if (given('autofocus', this.autofocus)) {
        control.autofocus = this.autofocus;
      }

      if (given('disabled', this.disabled)) {
        control.disabled = this.disabled;
      }

      if (
        given('readOnly', this.readOnly) &&
        !(control instanceof HTMLSelectElement)
      ) {
        control.readOnly = this.readOnly;
      }

      if (given('invalid', this.invalid)) {
        if (this.invalid) {
          control.setAttribute('aria-invalid', 'true');
        } else {
          control.removeAttribute('aria-invalid');
        }
      }
    }
  }

  return NativeFieldElement as unknown as Constructor<NativeFieldInterface> & T;
};
