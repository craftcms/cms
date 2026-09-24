import type {LitElement, PropertyValues} from 'lit';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** The public Lion choice-group surface the mixin drives. */
interface ChoiceGroupHost {
  name: string;
}

/**
 * Makes a Lion choice group (checkbox group, radio group) adopt its name from
 * the server-rendered inputs it wraps. Lion syncs each registered child's name
 * to the group's (`name || ''`), so a nameless group would strip the names the
 * server wrote and break native posting.
 *
 * Adoption runs before `super.connectedCallback()` so the name is in place
 * before any child registers, and again in `willUpdate` as a fallback for
 * streaming parsing, where the element can connect before its children exist.
 *
 * Returns `Base`'s own type so the subclass keeps Lion's statics and member
 * visibility; the mixin only adds lifecycle behavior. Pair it with
 * `SsrChoiceInputMixin`, which does the equivalent job for the inputs.
 */
export const SsrChoiceGroupMixin = <
  T extends Constructor<LitElement & ChoiceGroupHost>,
>(
  Base: T,
  inputType: 'checkbox' | 'radio'
): T => {
  class SsrChoiceGroup extends Base {
    private __ssrNameAdopted = false;

    override connectedCallback() {
      this.__adoptSlottedName();
      super.connectedCallback();
    }

    protected override willUpdate(changedProperties: PropertyValues) {
      this.__adoptSlottedName();
      super.willUpdate(changedProperties);
    }

    private __adoptSlottedName() {
      if (this.__ssrNameAdopted || this.name) {
        return;
      }

      const input = this.querySelector<HTMLInputElement>(
        `input[type="${inputType}"][name]`
      );

      if (!input) {
        // Leave the flag unset so a later pass can still adopt once children
        // have been parsed.
        return;
      }

      this.__ssrNameAdopted = true;
      this.name = input.name;
    }
  }

  return SsrChoiceGroup as T;
};
