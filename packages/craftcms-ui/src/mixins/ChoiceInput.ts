import {css, html, type LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {
  FormControl,
  formControlStyles,
  type FormControlInterface,
} from './FormControl';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/**
 * What a choice toggles: a native checkbox or radio, or an element that
 * behaves like one. It fires a bubbling `change` when the user toggles it.
 */
export interface ChoiceControlElement extends HTMLElement {
  checked: boolean;
  disabled: boolean;
}

export declare class ChoiceInputInterface extends FormControlInterface {
  checked: boolean;
  get value(): string;
  set value(value: string);
  name: string;
  required: boolean;
  invalid: boolean;
  get _inputNode(): ChoiceControlElement | undefined;
  protected get _defaultValue(): string;
  protected _pushToControl(changed: PropertyValues): void;
  _syncFromControl(): void;
}

/** The layout of a choice: the control, then its label, help text below. */
export const choiceInputStyles = [
  formControlStyles,
  css`
    :host {
      display: flex;
      flex-wrap: wrap;
    }

    .choice-field__help-text {
      display: block;
      flex-basis: 100%;
    }

    /*
     * Clipped rather than hidden, so the label and help text stay in the
     * accessibility tree. A focusable link in the help text shows on focus.
     */
    :host([label-sr-only]) .choice-field__label,
    :host([label-sr-only]) .choice-field__help-text:not(:focus-within) {
      position: absolute;
      width: 1px;
      height: 1px;
      overflow: hidden;
      clip-path: inset(100%);
      clip: rect(1px, 1px, 1px, 1px);
      white-space: nowrap;
      border: 0;
      margin: 0;
      padding: 0;
    }
  `,
];

function splitIds(value: string | null): string[] {
  return (value ?? '').split(/\s+/).filter(Boolean);
}

/**
 * A choice that's on or off: a checkbox, a radio, a switch. The control in its
 * light DOM holds the state, so `checked` always reads what the control says,
 * whoever changed it. The control's own `input` and `change` events are the
 * component's; setting `checked` from code is silent, like on a native input.
 *
 * Server-rendered markup is adopted as it is: the control's `checked`,
 * `disabled`, `name`, `value` and ARIA references stay, and host attributes
 * only override them when they're given.
 *
 * Subclasses pick their control with an `input` entry in `slots`.
 */
export const ChoiceInput = <T extends Constructor<LitElement>>(Base: T) => {
  class ChoiceInputElement extends FormControl(Base) {
    static styles = choiceInputStyles;

    /** Whether the choice is on. */
    @property({type: Boolean, reflect: true})
    get checked(): boolean {
      return this._inputNode?.checked ?? this.#pendingChecked ?? false;
    }

    set checked(checked: boolean) {
      const old = this.checked;
      const control = this._inputNode;

      if (control) {
        control.checked = checked;
      } else {
        this.#pendingChecked = checked;
      }

      this.requestUpdate('checked', old);
    }

    /** What the choice posts, and stands for in a group, when it's on. */
    @property()
    get value(): string {
      return this.#value ?? this.#nativeInput?.value ?? this._defaultValue;
    }

    set value(value: string) {
      const old = this.value;
      this.#value = value == null ? undefined : String(value);
      this.requestUpdate('value', old);
    }

    /** The control's `name`. */
    @property({reflect: true}) name = '';

    /** Marks the choice as required: sets `aria-required` on the control. */
    @property({type: Boolean}) required = false;

    /** Marks the choice as invalid: sets `aria-invalid` on the control. */
    @property({type: Boolean, reflect: true}) invalid = false;

    /** A `checked` given before the control exists. */
    #pendingChecked?: boolean;

    #value?: string;

    /** The `checked` the attribute last reflected. */
    #reflectedChecked = false;

    /** Whether host properties have been pushed onto the control yet. */
    #pushed = false;

    #adopted?: ChoiceControlElement;

    /** Where a form reset is heard: the tree the input and its form share. */
    #resetRoot?: Node;

    override get _inputNode(): ChoiceControlElement | undefined {
      return super._inputNode as ChoiceControlElement | undefined;
    }

    get #nativeInput(): HTMLInputElement | undefined {
      const control = this._inputNode;
      return control instanceof HTMLInputElement ? control : undefined;
    }

    /** The value when neither the host nor the control gives one. */
    protected get _defaultValue(): string {
      return '';
    }

    override connectedCallback() {
      super.connectedCallback();
      this.#adoptControl();
      this.addEventListener('change', this.#onChange);
      this.addEventListener('focusin', this.#onFocusIn);
      this.addEventListener('focusout', this.#onFocusOut);
      this.#resetRoot = this.getRootNode();
      this.#resetRoot.addEventListener('reset', this.#onReset, true);
    }

    override disconnectedCallback() {
      super.disconnectedCallback();
      this.#resetRoot?.removeEventListener('reset', this.#onReset, true);
      this.removeEventListener('change', this.#onChange);
      this.removeEventListener('focusin', this.#onFocusIn);
      this.removeEventListener('focusout', this.#onFocusOut);
    }

    /** Fallback for streaming parsing, where the control arrives late. */
    protected override willUpdate(changed: PropertyValues) {
      super.willUpdate(changed);
      this.#adoptControl();
    }

    protected override updated(changed: PropertyValues) {
      super.updated(changed);
      this._pushToControl(changed);
      this.#reflectedChecked = this.checked;
    }

    /**
     * Takes on the state of a control that was there before the host
     * upgraded, and keeps the host in step with writes made to the control
     * directly, which fire no event.
     */
    #adoptControl(): void {
      const control = this._inputNode;

      if (!control || control === this.#adopted) {
        return;
      }

      this.#adopted = control;
      // A custom control parsed with the host has to be one before it's read.
      customElements.upgrade(control);

      if (this.#pendingChecked !== undefined) {
        control.checked = this.#pendingChecked;
        this.#pendingChecked = undefined;
      }

      if (control.disabled && !this.disabled) {
        this.disabled = true;
      }

      const name = control.getAttribute('name');
      if (!this.name && name) {
        this.name = name;
      }

      if (control instanceof HTMLInputElement) {
        this.#watch(control);
      }

      this._syncFromControl();
    }

    #watch(input: HTMLInputElement): void {
      const host = this;

      for (const key of ['checked', 'disabled'] as const) {
        const descriptor = Object.getOwnPropertyDescriptor(
          HTMLInputElement.prototype,
          key
        );

        if (!descriptor?.get || !descriptor.set) {
          continue;
        }

        const {get, set} = descriptor;

        Object.defineProperty(input, key, {
          configurable: true,
          enumerable: descriptor.enumerable,
          get() {
            return get.call(this);
          },
          set(value: boolean) {
            set.call(this, value);
            if (key === 'disabled') {
              if (host.disabled !== value) {
                host.disabled = value;
              }
            } else {
              host._syncFromControl();
            }
          },
        });
      }
    }

    /**
     * Re-reflects `checked` after the control changed on its own: toggled by
     * the user, written to directly, or unchecked by a radio beside it.
     */
    _syncFromControl(): void {
      const control = this._inputNode;

      if (control && control.checked !== this.#reflectedChecked) {
        this.requestUpdate('checked', this.#reflectedChecked);
      }
    }

    #onChange = (event: Event): void => {
      if (event.target === this._inputNode) {
        this._syncFromControl();
      }
    };

    /** A form reset puts the input back silently, once its event is done. */
    #onReset = (event: Event): void => {
      if (event.target === this.#nativeInput?.form) {
        setTimeout(() => this._syncFromControl());
      }
    };

    #onFocusIn = (event: FocusEvent): void => {
      const control = this._inputNode;

      if (event.target !== control) {
        return;
      }

      // Styles keyed to keyboard focus on a slotted control can't read its
      // :focus-visible from the shadow root.
      this.toggleAttribute(
        'focused-visible',
        control.matches(':focus-visible')
      );
    };

    #onFocusOut = (event: FocusEvent): void => {
      if (event.target !== this._inputNode) {
        return;
      }

      this.removeAttribute('focused-visible');
    };

    override focus(options?: FocusOptions): void {
      this._inputNode?.focus(options);
    }

    override blur(): void {
      this._inputNode?.blur();
    }

    /**
     * Keeps references the control arrived with, which would otherwise be
     * replaced by the label and help text.
     */
    protected override _enhanceLightDomA11y(): void {
      const control = this._inputNode;
      const root = this.getRootNode() as Document | ShadowRoot;

      if (control && 'getElementById' in root) {
        const resolve = (attribute: string) =>
          splitIds(control.getAttribute(attribute))
            .map((id) => root.getElementById(id))
            .filter((node): node is HTMLElement => node instanceof HTMLElement);

        for (const node of resolve('aria-labelledby')) {
          this.addToAriaLabelledBy(node);
        }
        for (const node of resolve('aria-describedby')) {
          this.addToAriaDescribedBy(node);
        }
      }

      super._enhanceLightDomA11y();
    }

    /**
     * Pushes changed host properties onto the control. On the first render
     * only the ones that were given are, so server-rendered markup keeps its
     * own.
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
      const toggle = (attribute: string, on: boolean) => {
        if (on) {
          control.setAttribute(attribute, 'true');
        } else {
          control.removeAttribute(attribute);
        }
      };

      const input = this.#nativeInput;

      if (input && given('name', this.name)) {
        input.name = this.name;
      }

      // An empty value is given too: only a missing one leaves the input's own.
      if (input && changed.has('value') && this.#value !== undefined) {
        input.value = this.#value;
      }

      if (given('disabled', this.disabled)) {
        control.disabled = this.disabled;
      }

      if (given('required', this.required)) {
        toggle('aria-required', this.required);
      }

      if (given('invalid', this.invalid)) {
        toggle('aria-invalid', this.invalid);
      }
    }

    protected override render(): unknown {
      return html`<slot name="input"></slot>
        <div class="choice-field__label">
          <slot name="label"></slot>
        </div>
        <small class="choice-field__help-text">
          <slot name="help-text"></slot>
        </small>`;
    }
  }

  return ChoiceInputElement as unknown as Constructor<ChoiceInputInterface> & T;
};
