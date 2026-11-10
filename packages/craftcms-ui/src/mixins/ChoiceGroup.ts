import {html, type LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {FormControl, type FormControlInterface} from './FormControl';
import type {ChoiceInputInterface} from './ChoiceInput';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** How far each arrow key moves through the options, in DOM order. */
const ARROW_STEPS: Record<string, number> = {
  ArrowDown: 1,
  ArrowRight: 1,
  ArrowUp: -1,
  ArrowLeft: -1,
};

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
 * have, so the markup keeps posting the way it did. Radios with no name at all
 * aren't grouped by the browser, so the group makes them one Tab stop and moves
 * the selection with the arrow keys itself.
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

    /**
     * Whether the group has had a name. From then on it names its choices,
     * with an empty name too, rather than taking one from their inputs.
     */
    #named = false;

    /** Choices the group disabled, which it enables again with itself. */
    #disabledByGroup = new WeakSet<ChoiceInputInterface>();

    /** Inputs given a `tabindex` to make unnamed radios one Tab stop. */
    #tabStopInputs = new WeakSet<HTMLElement>();

    #observer = new MutationObserver((records) => {
      this.#refreshChoices();

      // A radio checked from code is on its own until its siblings are
      // unchecked, which radios with a shared name do themselves.
      if (!multiple) {
        const options = this.choices;
        const checked = records
          .map((record) => record.target as unknown as ChoiceInputInterface)
          .reverse()
          .find(
            (option) =>
              options.includes(option) &&
              option.checked &&
              (option as unknown as Element).hasAttribute('checked')
          );

        if (checked) {
          this.#uncheckOthers(checked);
        } else {
          for (const option of options) {
            option._syncFromControl?.();
          }
        }
      }

      this.#syncTabStops();
    });

    /** The choices in the group, leaving out those of a group nested in it. */
    get choices(): ChoiceInputInterface[] {
      return [...this.querySelectorAll(choice)].filter(
        (element) => element.parentElement?.closest(this.localName) === this
      ) as unknown as ChoiceInputInterface[];
    }

    /**
     * The values of the choices that are on, disabled or not: an array in a
     * group where any number can be, and a string, empty when none is, where
     * one can be.
     */
    @property({attribute: false})
    get value(): string | string[] {
      const values = this.choices
        .filter((option) => option.checked)
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
      this.addEventListener('keydown', this.#onKeyDown);
      // Radios reflect `checked` and `disabled`, which move the Tab stop.
      this.#observer.observe(this, {
        childList: true,
        subtree: true,
        ...(multiple
          ? {}
          : {attributes: true, attributeFilter: ['checked', 'disabled']}),
      });
      this.#known = new Set();
      this.#refreshChoices();
    }

    override disconnectedCallback() {
      super.disconnectedCallback();
      this.removeEventListener('input', this.#onToggle, true);
      this.removeEventListener('change', this.#onToggle, true);
      this.removeEventListener('keydown', this.#onKeyDown);
      this.#observer.disconnect();
    }

    protected override willUpdate(changed: PropertyValues) {
      super.willUpdate(changed);
      this.#adoptName();
    }

    protected override updated(changed: PropertyValues) {
      super.updated(changed);

      if (changed.has('name') && this.#named) {
        for (const option of this.choices) {
          option.name = this.name;
        }
      }

      if (changed.has('disabled') && changed.get('disabled') !== undefined) {
        for (const option of this.choices) {
          if (this.disabled) {
            this.#disable(option);
          } else if (this.#disabledByGroup.delete(option)) {
            option.disabled = false;
          }
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

      this.#syncTabStops();
    }

    protected override _inputGroupTemplate(): unknown {
      return html`<div class="input-group"><slot></slot></div>`;
    }

    /** Called when choices are added to or removed from the group. */
    protected _onChoicesChanged(): void {}

    #adoptName(): void {
      if (this.name) {
        this.#named = true;
      }

      if (this.#named) {
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

    #disable(option: ChoiceInputInterface): void {
      if (!this.#disabledOnItsOwn(option)) {
        option.disabled = true;
        this.#disabledByGroup.add(option);
      }
    }

    /**
     * Whether a choice is disabled itself. One that hasn't upgraded yet hasn't
     * taken on its markup's state, so that's read from the markup.
     */
    #disabledOnItsOwn(option: ChoiceInputInterface): boolean {
      const element = option as unknown as Element;
      const control = element.querySelector(':scope > [slot="input"]');

      return (
        option.disabled ||
        element.hasAttribute('disabled') ||
        (control instanceof HTMLInputElement && control.disabled)
      );
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
        if (this.#named) {
          option.name = this.name;
        }
        if (this.disabled) {
          this.#disable(option);
        }
      }

      if (this.#requested) {
        this.#apply(added, this.#requested);
      }

      this._onChoicesChanged();
      this.requestUpdate('value');
    }

    /** The choice of this group whose control is `target`. */
    #choiceOf(target: EventTarget | null): ChoiceInputInterface | undefined {
      const option = (target as Element).closest(
        choice
      ) as unknown as ChoiceInputInterface | null;

      return option?._inputNode === target &&
        (option as unknown as Element).parentElement?.closest(
          this.localName
        ) === this
        ? option
        : undefined;
    }

    /**
     * Leaves only the checked radio, or the first enabled one, in the Tab
     * order, as the browser does for radios that share a name.
     */
    #syncTabStops(): void {
      if (multiple) {
        return;
      }

      const options = this.choices;
      const stop = this.name
        ? undefined
        : (options.find((option) => option.checked && !option.disabled) ??
          options.find((option) => !option.disabled));

      for (const option of options) {
        const input = option._inputNode;

        if (!input) {
          continue;
        } else if (!this.name) {
          input.tabIndex = option === stop ? 0 : -1;
          this.#tabStopInputs.add(input);
        } else if (this.#tabStopInputs.delete(input)) {
          input.removeAttribute('tabindex');
        }
      }
    }

    /** Moves the selection between unnamed radios, as between named ones. */
    #onKeyDown = (event: KeyboardEvent): void => {
      const step = ARROW_STEPS[event.key];
      const option = this.#choiceOf(event.target);

      if (
        multiple ||
        this.name ||
        !step ||
        !option ||
        event.altKey ||
        event.ctrlKey ||
        event.metaKey
      ) {
        return;
      }

      const options = this.choices.filter((other) => !other.disabled);
      const mirrored =
        event.key !== 'ArrowDown' &&
        event.key !== 'ArrowUp' &&
        getComputedStyle(this).direction === 'rtl';
      const next = options.at(
        (options.indexOf(option) + (mirrored ? -step : step)) % options.length
      );

      if (!next) {
        return;
      }

      event.preventDefault();
      next._inputNode?.focus();

      if (!next.checked) {
        next._inputNode?.click();
      }
    };

    /** Unchecks every radio but `option`, silently, as a shared name would. */
    #uncheckOthers(option: ChoiceInputInterface): void {
      for (const other of this.choices) {
        if (other !== option && other.checked) {
          other.checked = false;
        }
        other._syncFromControl?.();
      }
    }

    #onToggle = (event: Event): void => {
      const option = this.#choiceOf(event.target);

      if (!option) {
        return;
      }

      this.#requested = undefined;
      option._syncFromControl();

      if (!multiple && option.checked) {
        this.#uncheckOthers(option);
      }

      // Reading the value walks every choice, so do it once per toggle.
      if (event.type === 'change') {
        this.requestUpdate('value');
      }
    };
  }

  return ChoiceGroupElement as unknown as Constructor<ChoiceGroupInterface> & T;
};
