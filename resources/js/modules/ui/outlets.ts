import {computed, reactive, type ComputedRef} from 'vue';

/**
 * Named places where UI nodes can render from elsewhere in the form, such as
 * the field layout designer's Appearance tab. A control that renders its own
 * markup registers each `[data-ui-outlet]` element it contains, and a group
 * with a matching `outlet` teleports into it, falling back to its own place
 * while there's no outlet.
 */
const outlets = reactive(new Map<string, HTMLElement>());

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
