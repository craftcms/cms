import {inject, provide, reactive, type InjectionKey} from 'vue';

/**
 * Tracks which named layout regions a page has filled via `<LayoutSlot>`.
 *
 * A screen shell (`PageScreen`, `SlideoutScreen`) creates one registry and
 * provides it; `LayoutSlotOutlet` queries it to reactively toggle its wrapper
 * markup and fallback content, and `LayoutSlot` registers against it and
 * teleports into the matching outlet.
 *
 * The registry is per-shell rather than global because a slideout keeps the
 * base page mounted underneath it. Two screens are live at once, and a shared
 * registry would make the base page light up outlets the slideout filled —
 * and, worse, make the slideout's `<LayoutSlot>` teleport into the base page's
 * outlet, since a bare `[data-layout-slot=…]` selector matches the first one
 * in document order.
 */
export interface LayoutSlotRegistry {
  /** Distinguishes this shell's outlets in the DOM. */
  scope: string;
  register(name: string): void;
  unregister(name: string): void;
  has(name: string): boolean;
}

export const LayoutSlotRegistryKey: InjectionKey<LayoutSlotRegistry> =
  Symbol('layoutSlotRegistry');

let nextScope = 0;

export function createLayoutSlotRegistry(scope?: string): LayoutSlotRegistry {
  const counts = reactive(new Map<string, number>());

  return {
    scope: scope ?? `screen-${++nextScope}`,

    register(name) {
      counts.set(name, (counts.get(name) ?? 0) + 1);
    },

    unregister(name) {
      const next = (counts.get(name) ?? 0) - 1;

      if (next <= 0) {
        counts.delete(name);
      } else {
        counts.set(name, next);
      }
    },

    has(name) {
      return (counts.get(name) ?? 0) > 0;
    },
  };
}

/**
 * The ambient registry, used by `<LayoutSlot>`/`<LayoutSlotOutlet>` mounted
 * outside any screen shell. Shared, but nothing renders a second copy of it.
 */
const defaultRegistry = createLayoutSlotRegistry('default');

/** Called by a screen shell during setup. */
export function provideLayoutSlotRegistry(scope?: string): LayoutSlotRegistry {
  const registry = createLayoutSlotRegistry(scope);
  provide(LayoutSlotRegistryKey, registry);

  return registry;
}

/**
 * Resolves the registry of the nearest enclosing shell.
 *
 * Vue resolves `inject` through slot content via the rendering parent, so a
 * page written as `<AppLayout><LayoutSlot/></AppLayout>` reaches the shell
 * `AppLayout` rendered — not whatever the page itself provides. See
 * `layoutSlots.test.ts`.
 */
export function useLayoutSlotRegistry(): LayoutSlotRegistry {
  return inject(LayoutSlotRegistryKey, defaultRegistry);
}
