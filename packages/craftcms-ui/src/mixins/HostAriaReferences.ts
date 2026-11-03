import {dedupeMixin} from '@open-wc/dedupe-mixin';
import type {LitElement} from 'lit';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** The Lion `FormControlMixin` members the mixin calls. */
interface AriaReferencingControl extends LitElement {
  addToAriaLabelledBy(
    element: HTMLElement,
    config?: {idPrefix?: string; reorder?: boolean}
  ): void;
  removeFromAriaLabelledBy(element: HTMLElement): void;
  addToAriaDescribedBy(
    element: HTMLElement,
    config?: {idPrefix?: string; reorder?: boolean}
  ): void;
  removeFromAriaDescribedBy(element: HTMLElement): void;
}

const REFERENCES = {
  'aria-labelledby': ['addToAriaLabelledBy', 'removeFromAriaLabelledBy'],
  'aria-describedby': ['addToAriaDescribedBy', 'removeFromAriaDescribedBy'],
} as const;

type ReferenceAttribute = keyof typeof REFERENCES;

const ATTRIBUTES = Object.keys(REFERENCES) as ReferenceAttribute[];

/**
 * Lets markup name and describe a Lion form control by id, the way it would a
 * native input: `aria-labelledby` and `aria-describedby` on the host reference
 * elements the control doesn't own — a table column heading, say, or
 * instructions rendered elsewhere in the form.
 *
 * Lion rebuilds its control's ARIA references from the elements it tracks, so
 * an id written onto the control directly is overwritten. Instead, each id is
 * looked up in the host's tree and the element handed to Lion, which names the
 * control after it alongside its own label, help text and feedback. The ids are
 * read when the control connects and whenever either attribute changes, so the
 * elements they reference need to be in the tree by then.
 *
 * Lion documents no way to do this: its `data-label` and `data-description`
 * only reach content inside the field. The approach follows `craft-switch`,
 * and relies on `addToAriaLabelledBy()` and `removeFromAriaLabelledBy()` (and
 * their `DescribedBy` pairs) in Lion's `FormControlMixin`.
 *
 * Deduped, as Lion's own mixins are, so a class that inherits it already
 * doesn't apply it twice.
 *
 * ```ts
 * class CraftThing extends HostAriaReferences(HasLabel(LionField)) {}
 * ```
 */
export const HostAriaReferences = dedupeMixin(
  <T extends Constructor<AriaReferencingControl>>(Base: T) => {
    class HostAriaReferencesElement extends Base {
      #observer = new MutationObserver((records) => {
        for (const {attributeName} of records) {
          this.#adopt(attributeName as ReferenceAttribute);
        }
      });

      #adopted: Record<ReferenceAttribute, HTMLElement[]> = {
        'aria-labelledby': [],
        'aria-describedby': [],
      };

      override connectedCallback() {
        super.connectedCallback();
        this.#observer.observe(this, {attributeFilter: ATTRIBUTES});
        for (const attribute of ATTRIBUTES) {
          this.#adopt(attribute);
        }
      }

      override disconnectedCallback() {
        this.#observer.disconnect();
        super.disconnectedCallback();
      }

      #adopt(attribute: ReferenceAttribute) {
        const [add, remove] = REFERENCES[attribute];
        const root = this.getRootNode() as Document | ShadowRoot;
        const elements = (this.getAttribute(attribute) ?? '')
          .split(/\s+/)
          .filter(Boolean)
          .map((id) => root.getElementById(id))
          .filter((element): element is HTMLElement => element !== null);

        for (const element of this.#adopted[attribute]) {
          if (!elements.includes(element)) {
            this[remove](element);
          }
        }
        for (const element of elements) {
          this[add](element, {reorder: false});
        }
        this.#adopted[attribute] = elements;
      }
    }

    return HostAriaReferencesElement as T;
  }
);
