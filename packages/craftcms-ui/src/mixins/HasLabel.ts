import {html, type LitElement, type PropertyValues} from 'lit';
import {FieldNotices} from './FieldNotices';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** The Lion `FormControlMixin` members the mixin reads. */
interface LabeledFormControl extends LitElement {
  label: string;
  helpText: string;
  addToAriaDescribedBy(
    element: HTMLElement,
    config?: {idPrefix?: string; reorder?: boolean}
  ): void;
}

/**
 * The field chrome a standalone Lion form control shares with `craft-field`,
 * so a labeled control lays out like any other CP field:
 *
 * - The label and help text render only when there's something to show, as
 *   in `craft-field`, so `baseFieldStyles` can space them unconditionally.
 * - `tip` and `warning` slots render as field notices (see `FieldNotices`).
 * - A `has-label` attribute reflects whether there's a label.
 *
 * A control counts as labeled when its `label` — the `label` attribute or
 * property, or the text of the slotted `[slot=label]` element — isn't blank,
 * or when the slotted label contains an element (an icon, say). Help text
 * counts the same way. Both are re-checked after every update, and when the
 * slotted content changes outside of Lit.
 *
 * ```ts
 * class CraftThing extends HasLabel(LionInput) {}
 * ```
 */
export const HasLabel = <T extends Constructor<LabeledFormControl>>(
  Base: T
) => {
  class HasLabelElement extends FieldNotices(Base) {
    #observer = new MutationObserver(() => this.#sync());

    #hasLabel = false;

    #hasHelpText = false;

    override connectedCallback() {
      super.connectedCallback();
      this.#observer.observe(this, {
        childList: true,
        subtree: true,
        characterData: true,
      });
      this.#sync();
    }

    override disconnectedCallback() {
      this.#observer.disconnect();
      super.disconnectedCallback();
    }

    protected override willUpdate(changedProperties: PropertyValues) {
      super.willUpdate(changedProperties);
      this.#hasLabel = this.#hasContent(this.label, 'label');
      this.#hasHelpText = this.#hasContent(this.helpText, 'help-text');
    }

    protected override updated(changedProperties: PropertyValues) {
      super.updated(changedProperties);
      this.#sync();
    }

    protected _labelTemplate() {
      return this.#hasLabel
        ? // @ts-expect-error The base is typed without Lion's protected members.
          super._labelTemplate()
        : html``;
    }

    protected _helpTextTemplate() {
      return this.#hasHelpText
        ? // @ts-expect-error The base is typed without Lion's protected members.
          super._helpTextTemplate()
        : html``;
    }

    #hasContent(text: string, slot: string): boolean {
      const node = this.querySelector(`:scope > [slot="${slot}"]`);

      return text.trim() !== '' || (node?.childElementCount ?? 0) > 0;
    }

    #sync() {
      const hasLabel = this.#hasContent(this.label, 'label');
      const hasHelpText = this.#hasContent(this.helpText, 'help-text');

      this.toggleAttribute('has-label', hasLabel);

      if (hasLabel !== this.#hasLabel || hasHelpText !== this.#hasHelpText) {
        this.requestUpdate();
      }
    }
  }

  return HasLabelElement as T;
};
