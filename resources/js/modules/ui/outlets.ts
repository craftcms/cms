import {
  computed,
  inject,
  reactive,
  type ComputedRef,
  type InjectionKey,
} from 'vue';

/**
 * Named places where UI nodes can render from elsewhere in the form, such as
 * the field layout designer's Appearance tab. A control that renders its own
 * markup registers each `[data-ui-outlet]` element it contains, and a group
 * with a matching `outlet` teleports into it, falling back to its own place
 * while there's no outlet. A control that fetches its markup can mark its
 * outlets as pending until then, so groups wait for them rather than render
 * in place first and jump.
 *
 * Each form has outlets of its own (see {@link UiOutletsKey}), so two forms
 * on screen at once, such as an entry type's and one in a slideout over it,
 * can use the same outlet names without moving each other's nodes.
 */
export type OutletRegistry = {
  /** Registers the outlets within `root`, returning a function that releases them. */
  registerAll(root: HTMLElement): () => void;
  /** Registers `element` as the named outlet, returning a function that releases it. */
  register(name: string, element: HTMLElement): () => void;
  /** The element registered as the named outlet, if there is one. */
  outlet(name: () => string | undefined): ComputedRef<HTMLElement | null>;
  /**
   * Marks the named outlets as about to be registered, returning a function
   * that clears the mark. Calling the function again has no effect.
   */
  expect(names: string[]): () => void;
  /** Whether the named outlet is expected but not registered yet. */
  pending(name: () => string | undefined): ComputedRef<boolean>;
};

export function createOutletRegistry(): OutletRegistry {
  const outlets = reactive(new Map<string, HTMLElement>());
  const pendingOutlets = reactive(new Map<string, number>());

  const register = (name: string, element: HTMLElement): (() => void) => {
    outlets.set(name, element);

    return () => {
      if (outlets.get(name) === element) {
        outlets.delete(name);
      }
    };
  };

  return {
    register,
    registerAll(root) {
      const releases = [
        ...root.querySelectorAll<HTMLElement>('[data-ui-outlet]'),
      ].map((element) => register(element.dataset.uiOutlet!, element));

      return () => releases.forEach((release) => release());
    },
    outlet(name) {
      return computed(() => {
        const outletName = name();

        return outletName ? (outlets.get(outletName) ?? null) : null;
      });
    },
    expect(names) {
      for (const name of names) {
        pendingOutlets.set(name, (pendingOutlets.get(name) ?? 0) + 1);
      }

      let released = false;

      return () => {
        if (released) {
          return;
        }

        released = true;

        for (const name of names) {
          const count = (pendingOutlets.get(name) ?? 0) - 1;

          if (count > 0) {
            pendingOutlets.set(name, count);
          } else {
            pendingOutlets.delete(name);
          }
        }
      };
    },
    pending(name) {
      return computed(() => {
        const outletName = name();

        return Boolean(
          outletName &&
          !outlets.has(outletName) &&
          pendingOutlets.has(outletName)
        );
      });
    },
  };
}

/** The outlets of the form a node renders in, provided by `UiRenderer`. */
export const UiOutletsKey: InjectionKey<OutletRegistry> = Symbol('UiOutlets');

/** Shared by the nodes that render outside any form. */
const sharedOutlets = createOutletRegistry();

/** The outlets of the form rendering the caller. Call it during setup. */
export function useOutlets(): OutletRegistry {
  return inject(UiOutletsKey, sharedOutlets);
}
