import {dedupeMixin} from '@open-wc/dedupe-mixin';
import type {LitElement, PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';

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

/** The properties the mixin adds. */
export declare class HostAriaReferencesHost {
  /**
   * Ids of elements elsewhere that label the control, such as a table column
   * heading. Set with the `labelled-by` attribute.
   */
  labelledBy: string | null;

  /**
   * Ids of elements elsewhere that describe the control. Set with the
   * `described-by` attribute.
   */
  describedBy: string | null;
}

const REFERENCES = {
  labelledBy: ['addToAriaLabelledBy', 'removeFromAriaLabelledBy'],
  describedBy: ['addToAriaDescribedBy', 'removeFromAriaDescribedBy'],
} as const;

type Reference = keyof typeof REFERENCES;

const PROPERTIES = Object.keys(REFERENCES) as Reference[];

/**
 * Lets markup name and describe a Lion form control by id: `labelled-by` and
 * `described-by` on the host reference elements the control doesn't own — a
 * table column heading, say, or instructions rendered elsewhere in the form.
 * They aren't `aria-labelledby` and `aria-describedby` because the host isn't
 * the control: the markup shows which element people interact with, and which
 * only passes the references on.
 *
 * Lion rebuilds its control's ARIA references from the elements it tracks, so
 * an id written onto the control directly is overwritten. Instead, each id is
 * looked up in the host's tree and the element handed to Lion, which names the
 * control after it alongside its own label, help text and feedback. The ids are
 * read when the control connects and whenever either property changes, so the
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
      @property({attribute: 'labelled-by'}) labelledBy: string | null = null;

      @property({attribute: 'described-by'}) describedBy: string | null = null;

      #adopted: Record<Reference, HTMLElement[]> = {
        labelledBy: [],
        describedBy: [],
      };

      override connectedCallback() {
        super.connectedCallback();
        for (const reference of PROPERTIES) {
          this.#adopt(reference);
        }
      }

      protected override updated(changed: PropertyValues) {
        super.updated(changed);
        for (const reference of PROPERTIES) {
          if (changed.has(reference)) {
            this.#adopt(reference);
          }
        }
      }

      #adopt(reference: Reference) {
        const [add, remove] = REFERENCES[reference];
        // When disconnected, getRootNode() may return a root (the element
        // itself or a bare DocumentFragment) without getElementById.
        // connectedCallback() adopts again.
        const root = this.getRootNode() as Document | ShadowRoot;
        if (typeof root.getElementById !== 'function') {
          return;
        }
        const elements = (this[reference] ?? '')
          .split(/\s+/)
          .filter(Boolean)
          .map((id) => root.getElementById(id))
          .filter((element): element is HTMLElement => element !== null);

        for (const element of this.#adopted[reference]) {
          if (!elements.includes(element)) {
            this[remove](element);
          }
        }
        for (const element of elements) {
          this[add](element, {reorder: false});
        }
        this.#adopted[reference] = elements;
      }
    }

    return HostAriaReferencesElement as unknown as Constructor<HostAriaReferencesHost> &
      T;
  }
);
