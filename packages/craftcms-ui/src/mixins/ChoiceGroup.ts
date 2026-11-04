import {html, type LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {FormControl, type FormControlInterface} from './FormControl';
import type {ChoiceInputInterface} from './ChoiceInput';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

export type ChoiceGroupOptions = {
  /** The tag of the choices the group holds. */
  choice: string;
  /** Whether any number can be on, rather than at most one. */
  multiple: boolean;
};

export declare class ChoiceGroupInterface extends FormControlInterface {
  name: string;
  value: string | string[];
  required: boolean;
  invalid: boolean;
  readonly multipleChoice: boolean;
  get choices(): ChoiceInputInterface[];
  protected _onChoicesChanged(): void;
}

/**
 * A labeled group of choices sharing one name: checkboxes, or radios of which
 * at most one is on. The group's `value` is what's on, read from the choices
 * themselves, and the choices' own `input` and `change` events bubble through
 * it, so a listener on the group reads the new value. Setting `value` from
 * code is silent.
 *
 * A group without a `name` takes the one its server-rendered inputs already
 * have, so the markup keeps posting the way it did.
 */
export const ChoiceGroup = <T extends Constructor<LitElement>>(
  Base: T,
  {choice, multiple}: ChoiceGroupOptions
) => {
  class ChoiceGroupElement extends FormControl(Base) {
    /** The name every choice in the group posts under. */
    @property({reflect: true}) name = '';

    /**
     * Marks a choice as required. A radio group says so with `aria-required`;
     * a checkbox group has no way to, so its label has to.
     */
    @property({type: Boolean}) required = false;

    /** Marks the choice as invalid: sets `aria-invalid` on the group. */
    @property({type: Boolean, reflect: true}) invalid = false;

    readonly multipleChoice = multiple;

    /** The value last set from code, for choices that arrive after it. */
    #requested?: string[];

    #known = new Set<Element>();

    #observer = new MutationObserver(() => this.#refreshChoices());

    /** The choices in the group, leaving out those of a group nested in it. */
    get choices(): ChoiceInputInterface[] {
      return [...this.querySelectorAll(choice)].filter(
        (element) => element.parentElement?.closest(this.localName) === this
      ) as unknown as ChoiceInputInterface[];
    }

    /**
     * The values of the choices that are on: an array in a group where any
     * number can be, and a string, empty when none is, where one can be.
     */
    @property({attribute: false})
    get value(): string | string[] {
      const values = this.choices
        .filter((option) => option.checked && !option.disabled)
        .map((option) => option.value);

      return multiple ? values : (values[0] ?? '');
    }

    set value(value: string | string[] | null | undefined) {
      const old = this.value;
      const values = (
        Array.isArray(value)
          ? value
          : value == null || value === ''
            ? []
            : [value]
      ).map(String);

      this.#requested = values;
      this.#apply(this.choices, values);
      this.requestUpdate('value', old);
    }

    /** The group is what the label names and the help text describes. */
    override get _inputNode(): HTMLElement {
      return this;
    }

    override connectedCallback() {
      this.#adoptName();
      super.connectedCallback();
      this.setAttribute('role', multiple ? 'group' : 'radiogroup');
      this._labelNode?.removeAttribute('for');
      this.classList.remove('form-control');
      // Capturing, so exclusivity is settled before any listener on the group
      // reads the value.
      this.addEventListener('input', this.#onToggle, true);
      this.addEventListener('change', this.#onToggle, true);
      this.#observer.observe(this, {childList: true, subtree: true});
      this.#known = new Set();
      this.#refreshChoices();
    }

    override disconnectedCallback() {
      super.disconnectedCallback();
      this.removeEventListener('input', this.#onToggle, true);
      this.removeEventListener('change', this.#onToggle, true);
      this.#observer.disconnect();
    }

    protected override willUpdate(changed: PropertyValues) {
      super.willUpdate(changed);
      this.#adoptName();
    }

    protected override updated(changed: PropertyValues) {
      super.updated(changed);

      if (changed.has('name') && this.name) {
        for (const option of this.choices) {
          option.name = this.name;
        }
      }

      if (changed.has('disabled') && changed.get('disabled') !== undefined) {
        for (const option of this.choices) {
          option.disabled = this.disabled;
        }
      }

      const toggle = (attribute: string, on: boolean) =>
        on
          ? this.setAttribute(attribute, 'true')
          : this.removeAttribute(attribute);

      // `group` doesn't support `aria-required`; `radiogroup` does.
      if (changed.has('required') && !multiple) {
        toggle('aria-required', this.required);
      }

      if (changed.has('invalid')) {
        toggle('aria-invalid', this.invalid);
      }
    }

    protected override _inputGroupTemplate(): unknown {
      return html`<div class="input-group"><slot></slot></div>`;
    }

    /** Called when choices are added to or removed from the group. */
    protected _onChoicesChanged(): void {}

    #adoptName(): void {
      if (this.name) {
        return;
      }

      const type = multiple ? 'checkbox' : 'radio';
      const input = this.querySelector<HTMLInputElement>(
        `input[type="${type}"][name]`
      );

      if (input) {
        this.name = input.name;
      }
    }

    #apply(options: ChoiceInputInterface[], values: string[]): void {
      let found = false;

      for (const option of options) {
        const on: boolean =
          values.includes(option.value) && (multiple || !found);
        found ||= on;
        option.checked = on;
      }
    }

    #refreshChoices(): void {
      const choices = this.choices;
      const added = choices.filter(
        (option) => !this.#known.has(option as never)
      );
      const removed = this.#known.size + added.length !== choices.length;

      if (!added.length && !removed) {
        return;
      }

      this.#known = new Set(choices as never[]);

      for (const option of added) {
        if (this.name) {
          option.name = this.name;
        }
        if (this.disabled) {
          option.disabled = true;
        }
      }

      if (this.#requested) {
        this.#apply(added, this.#requested);
      }

      this._onChoicesChanged();
      this.requestUpdate('value');
    }

    #onToggle = (event: Event): void => {
      const option = this.choices.find(
        (candidate) => candidate._inputNode === event.target
      );

      if (!option) {
        return;
      }

      this.#requested = undefined;

      for (const other of this.choices) {
        if (!multiple && option.checked && other !== option) {
          other.checked = false;
        }
        other._syncFromControl();
      }

      this.requestUpdate('value');
    };
  }

  return ChoiceGroupElement as unknown as Constructor<ChoiceGroupInterface> & T;
};
