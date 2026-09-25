import type {LitElement, PropertyValues} from 'lit';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** The Lion `FormControlMixin` members the mixin reads. */
interface LabelledFormControl extends LitElement {
  label: string;
}

/**
 * Reflects whether a Lion form control has a label onto a `has-label`
 * attribute, so styles that space the label from the control (such as
 * `baseFieldStyles`) only apply when there's a label to space.
 *
 * A control counts as labelled when its `label` — the `label` attribute or
 * property, or the text of the slotted `[slot=label]` element — isn't blank,
 * or when the slotted label contains an element (an icon, say). The attribute
 * is kept in sync after every update, and when the slotted label's content
 * changes outside of Lit.
 *
 * ```ts
 * class CraftThing extends HasLabel(LionInput) {}
 * ```
 */
export const HasLabel = <T extends Constructor<LabelledFormControl>>(
  Base: T
) => {
  class HasLabelElement extends Base {
    #observer = new MutationObserver(() => this.#syncHasLabel());

    override connectedCallback() {
      super.connectedCallback();
      this.#observer.observe(this, {
        childList: true,
        subtree: true,
        characterData: true,
      });
      this.#syncHasLabel();
    }

    override disconnectedCallback() {
      this.#observer.disconnect();
      super.disconnectedCallback();
    }

    protected override updated(changedProperties: PropertyValues) {
      super.updated(changedProperties);
      this.#syncHasLabel();
    }

    #syncHasLabel() {
      const labelNode = this.querySelector(':scope > [slot="label"]');

      this.toggleAttribute(
        'has-label',
        this.label.trim() !== '' || (labelNode?.childElementCount ?? 0) > 0
      );
    }
  }

  return HasLabelElement as T;
};
