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
  focused: boolean;
  get _inputNode(): NativeControl | undefined;
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

    /** Whether the control has focus. */
    @property({type: Boolean, reflect: true}) focused = false;

    /** Held until the control exists. */
    #pendingValue?: string;

    /** Whether host properties have been pushed onto the control yet. */
    #pushed = false;

    override get _inputNode(): NativeControl | undefined {
      return super._inputNode as NativeControl | undefined;
    }

    /** The control's value. */
    @property({attribute: false})
    get value(): string {
      return this._inputNode?.value ?? this.#pendingValue ?? '';
    }

    set value(value: string) {
      const old = this.value;
      const control = this._inputNode;

      if (!control) {
        this.#pendingValue = value;
      } else if (control.value !== value) {
        this.#setValueKeepingCaret(control, value);
      }

      this.requestUpdate('value', old);
    }

    #setValueKeepingCaret(control: NativeControl, value: string): void {
      if (
        this.focused &&
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

      if (this.#pendingValue !== undefined && this._inputNode) {
        this._inputNode.value = this.#pendingValue;
        this.#pendingValue = undefined;
      }

      this.addEventListener('input', this.#onInput);
      this.addEventListener('focusin', this.#onFocusIn);
      this.addEventListener('focusout', this.#onFocusOut);
    }

    override disconnectedCallback() {
      super.disconnectedCallback();
      this.removeEventListener('input', this.#onInput);
      this.removeEventListener('focusin', this.#onFocusIn);
      this.removeEventListener('focusout', this.#onFocusOut);
    }

    /** Re-renders on typing, so anything drawn from `value` follows it. */
    #onInput = (): void => {
      this.requestUpdate('value');
    };

    #onFocusIn = (event: FocusEvent): void => {
      if (event.target !== this._inputNode) {
        return;
      }

      this.focused = true;
      this.toggleAttribute(
        'focused-visible',
        this._inputNode.matches(':focus-visible')
      );
    };

    #onFocusOut = (event: FocusEvent): void => {
      if (event.target !== this._inputNode) {
        return;
      }

      this.focused = false;
      this.removeAttribute('focused-visible');
    };

    override focus(options?: FocusOptions): void {
      this._inputNode?.focus(options);
    }

    override blur(): void {
      this._inputNode?.blur();
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
      const control = this._inputNode;

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
