import type { LitElement, PropertyValues } from 'lit';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** The public Lion choice-input surface the mixin drives. */
interface ChoiceInputHost {
  name: string;
  value: string;
  choiceValue: unknown;
  checked: boolean;
  disabled: boolean;
}

/** Lion-protected members the mixin needs; accessed via cast. */
interface ChoiceInputInternals {
  _inputId: string;
  _inputNode: HTMLElement | undefined;
  _isHandlingUserInput: boolean;
  _toggleChecked(ev: Event): void;
}

/**
 * Makes a Lion choice input (checkbox, radio) adopt server-rendered state
 * from a slotted light-DOM input. Lion treats the host as the source of
 * truth and would otherwise push its defaults (no name, unchecked, empty
 * value) onto the adopted input — `LionField`'s connectedCallback already
 * reflects the empty formattedValue into the input, so adoption must happen
 * before `super.connectedCallback()`. It also aligns `_inputId` with the
 * SSR'd input's id so Lion's label `for` rewrite keeps label clicks working.
 *
 * Returns `Base`'s own type so the subclass keeps Lion's statics and member
 * visibility; the mixin only adds lifecycle behavior.
 */
export const SsrChoiceInputMixin = <T extends Constructor<LitElement & ChoiceInputHost>>(Base: T): T => {
  class SsrChoiceInput extends Base {
    private __ssrStateAdopted = false;

    /**
     * Guards Lion's constructor-time `value = ''` reflection. Checkbox and
     * radio inputs have no dirty-value state, so writing the `value`
     * property writes through to the content attribute — destroying the
     * server-rendered value before adoption gets a chance to run. Class
     * fields aren't initialized during `super()`, so `__ssrStateAdopted` is
     * `undefined` (treated as not-yet-adopted) when the guard matters.
     */
    override set value(value: string) {
      if (value === '' && !this.__ssrStateAdopted && this.__slottedInput()?.hasAttribute('value')) {
        return;
      }

      super.value = value;
    }

    override get value(): string {
      return super.value;
    }

    override connectedCallback() {
      this.__adoptSlottedInputState();
      super.connectedCallback();
    }

    /**
     * Derive the new state from the input the user just toggled, rather than
     * inverting the host's own.
     *
     * Lion toggles relatively (`this.checked = !this.checked`), which assumes
     * it is the only writer. It isn't: a consumer that also binds `checked`
     * declaratively — `ChoiceControl` renders `.checked="selected(…)"` on both
     * checkboxes and radios — updates its model from the input's `change` and
     * re-renders, and that write can land on the host *before* this handler
     * runs. The host then already holds the value the user produced, so
     * inverting it puts it straight back and the click is swallowed. Only
     * every second click registers.
     *
     * Reading the input instead is idempotent, so it lands on the same result
     * whether or not a re-render got there first. It is also the more correct
     * reading for a radio, where activating an already-checked one must leave
     * it checked rather than flip it off.
     *
     * `_toggleChecked` is bound in Lion's constructor off the prototype chain,
     * so this override is what gets subscribed to `user-input-changed`.
     */
    protected _toggleChecked(ev: Event) {
      const internals = this as unknown as ChoiceInputInternals;
      const input = internals._inputNode;

      if (this.disabled) {
        return;
      }

      if (!(input instanceof HTMLInputElement)) {
        // Nothing to read from — leave Lion's own behavior in place.
        Object.getPrototypeOf(SsrChoiceInput.prototype)._toggleChecked?.call(this, ev);

        return;
      }

      internals._isHandlingUserInput = true;
      this.checked = input.checked;
      internals._isHandlingUserInput = false;
    }

    /**
     * Fallback for streaming parsing, where the element can connect before
     * its children exist and the adoption in `connectedCallback` finds no
     * input.
     */
    protected override willUpdate(changedProperties: PropertyValues) {
      this.__adoptSlottedInputState();
      super.willUpdate(changedProperties);
    }

    /**
     * Keeps the native input's `value` in sync with `choiceValue`. The input
     * is the posting surface (native form submits, `serializeFormInputs`),
     * and Lion's format reflection can overwrite it with a stale
     * formattedValue on the first update cycle.
     */
    protected override updated(changedProperties: PropertyValues) {
      super.updated(changedProperties);

      const { _inputNode } = this as unknown as ChoiceInputInternals;
      const choiceValue = this.choiceValue;

      if (
        typeof choiceValue === 'string' &&
        choiceValue !== '' &&
        _inputNode instanceof HTMLInputElement &&
        _inputNode.value !== choiceValue
      ) {
        _inputNode.value = choiceValue;
      }
    }

    private __slottedInput(): HTMLInputElement | undefined {
      return Array.from(this.children).find(
        (child): child is HTMLInputElement => child instanceof HTMLInputElement && child.slot === 'input',
      );
    }

    private __adoptSlottedInputState() {
      if (this.__ssrStateAdopted) {
        return;
      }

      const input = this.__slottedInput();

      if (!input) {
        // Leave the flag unset so a later pass can still adopt once children
        // have been parsed.
        return;
      }

      this.__ssrStateAdopted = true;

      // Lion rewrites the label's `for` to `_inputId` on connect; keep it
      // pointing at the SSR'd input so label clicks keep toggling.
      if (input.id) {
        (this as unknown as ChoiceInputInternals)._inputId = input.id;
      }
      if (!this.name && input.name) {
        this.name = input.name;
      }

      // Read the value from the attribute, not the property: Lion's
      // constructor chain initializes `value = ''`, which writes into the
      // slotted input's property before this hook can run — but never
      // touches the attribute, so the server-rendered value survives there.
      const value = input.getAttribute('value') ?? input.value;

      if (value) {
        this.choiceValue = value;
        input.value = value;
      }
      if (input.checked) {
        this.checked = true;
      }
      if (input.disabled) {
        this.disabled = true;
      }
    }
  }

  return SsrChoiceInput as T;
};
