import {computed, reactive, type ComputedRef} from 'vue';

/**
 * Named places where UI nodes can render from elsewhere in the form, such as
 * the field layout designer's Appearance tab. A control that renders its own
 * markup registers each `[data-ui-outlet]` element it contains, and a group
 * with a matching `outlet` teleports into it, falling back to its own place
 * while there's no outlet. A control that fetches its markup can mark its
 * outlets as pending until then, so groups wait for them rather than render
 * in place first and jump.
 */
const outlets = reactive(new Map<string, HTMLElement>());
const pendingOutlets = reactive(new Map<string, number>());

/** Registers the outlets within `root`, returning a function that releases them. */
export function registerOutlets(root: HTMLElement): () => void {
  const registered = [
    ...root.querySelectorAll<HTMLElement>('[data-ui-outlet]'),
  ].map((element) => {
    const name = element.dataset.uiOutlet!;
    outlets.set(name, element);

    return [name, element] as const;
  });

  return () => {
    for (const [name, element] of registered) {
      if (outlets.get(name) === element) {
        outlets.delete(name);
      }
    }
  };
}

/** The element registered as the named outlet, if there is one. */
export function useOutlet(
  name: () => string | undefined
): ComputedRef<HTMLElement | null> {
  return computed(() => {
    const outletName = name();

    return outletName ? (outlets.get(outletName) ?? null) : null;
  });
}

/**
 * Marks the named outlets as about to be registered, returning a function
 * that clears the mark. Calling the function again has no effect.
 */
export function expectOutlets(names: string[]): () => void {
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
}

/** Whether the named outlet is expected but not registered yet. */
export function useOutletPending(
  name: () => string | undefined
): ComputedRef<boolean> {
  return computed(() => {
    const outletName = name();

    return Boolean(
      outletName && !outlets.has(outletName) && pendingOutlets.has(outletName)
    );
  });
}
