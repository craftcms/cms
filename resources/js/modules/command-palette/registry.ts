import {markRaw, shallowReactive, shallowRef} from 'vue';
import type {
  CommandPaletteItem,
  CommandPaletteProvider,
  CommandPaletteViewComponent,
} from './types';

/**
 * Whether the palette is showing. Shared so the header's search button, the
 * keyboard shortcut and plugins can all open the one palette.
 */
export const isCommandPaletteOpen = shallowRef(false);

const providers = shallowReactive<CommandPaletteProvider[]>([]);
const actionSources: Array<() => CommandPaletteItem[]> = [];
const views = new Map<string, CommandPaletteViewComponent>();

export function openCommandPalette(): void {
  isCommandPaletteOpen.value = true;
}

export function closeCommandPalette(): void {
  isCommandPaletteOpen.value = false;
}

/**
 * Adds a search provider that runs in the browser, such as one that searches
 * the page itself. Server-side providers are registered in PHP instead.
 */
export function registerProvider(provider: CommandPaletteProvider): void {
  const index = providers.findIndex(({handle}) => handle === provider.handle);

  if (index === -1) {
    providers.push(provider);
  } else {
    providers.splice(index, 1, provider);
  }
}

export function clientProviders(): readonly CommandPaletteProvider[] {
  return providers;
}

/**
 * Adds actions that depend on the page being viewed. The source is called
 * each time the palette opens, so it can read the page's current state.
 *
 * Returns a function that removes the source again, for a component whose
 * actions should go when it does.
 */
export function registerActions(
  source: () => CommandPaletteItem[]
): () => void {
  actionSources.push(source);

  return () => {
    const index = actionSources.indexOf(source);

    if (index !== -1) {
      actionSources.splice(index, 1);
    }
  };
}

export function clientActions(): CommandPaletteItem[] {
  return actionSources.flatMap((source) => {
    try {
      return source();
    } catch (error) {
      console.error('A command palette action source failed:', error);

      return [];
    }
  });
}

/**
 * Registers the component an item's `view` of this type renders.
 *
 * It receives `item` and `view` props, and emits `done` (optionally with a
 * `{message, redirect, reload}` result) once its work has succeeded, or `back`
 * to return to the search results.
 */
export function registerView(
  type: string,
  component: CommandPaletteViewComponent
): void {
  views.set(type, markRaw(component));
}

export function viewComponent(
  type: string
): CommandPaletteViewComponent | undefined {
  return views.get(type);
}
