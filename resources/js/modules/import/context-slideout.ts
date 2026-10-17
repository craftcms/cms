/**
 * Opening a slideout panel that needs a callback-bearing context rather than plain
 * props.
 *
 * Callbacks can't ride in `ScreenPageProps`, so the panel is handed a key instead and
 * claims its context on setup. `createContextRegistry` holds the claim map; `openContextSlideout`
 * does the claim, lazy-load and open in one step, shared by every panel in this feature
 * that works this way (a step's settings, its mapping, and a nested mapping).
 */
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';

export interface ContextRegistry<TContext> {
  register(context: TContext): string;
  take(contextId: string): TContext;
  discard(contextId: string): void;
}

export function createContextRegistry<TContext>(
  name: string
): ContextRegistry<TContext> {
  const contexts = new Map<string, TContext>();
  let nextId = 0;

  return {
    register(context: TContext): string {
      const contextId = `${name}-${++nextId}`;
      contexts.set(contextId, context);

      return contextId;
    },

    take(contextId: string): TContext {
      const context = contexts.get(contextId);

      if (!context) {
        throw new Error(`Unknown ${name} context: ${contextId}`);
      }

      contexts.delete(contextId);

      return context;
    },

    discard(contextId: string): void {
      contexts.delete(contextId);
    },
  };
}

/**
 * Registers `context`, opens `loadComponent`'s panel with it, and discards the context
 * again if the panel didn't open — either the user declined to discard unsaved changes
 * in a panel this one would have replaced, or the caller chose not to open one at all.
 */
export async function openContextSlideout<TContext>(
  registry: ContextRegistry<TContext>,
  loadComponent: () => Promise<InertiaPageComponent>,
  context: TContext,
  title: string,
  opener: HTMLElement | null
): Promise<boolean> {
  const [{openSlideoutWith}, component] = await Promise.all([
    import('@/common/slideouts'),
    loadComponent(),
  ]);

  const contextId = registry.register(context);

  // SAFETY: The slideout host renders this imported Vue SFC exactly like its Inertia
  // page components; it does not require an Inertia page module.
  const panel = openSlideoutWith(component, {contextId, title}, {opener});

  if (!panel) {
    registry.discard(contextId);

    return false;
  }

  return true;
}
