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

/** Whether it was opened with the keyboard shortcut, and so skips the enter transition. */
export const openedWithShortcut = shallowRef(false);

let shortcutInstalled = false;

/**
 * Opens and closes the palette with Cmd+K (Ctrl+K elsewhere), from anywhere.
 * Installed with the control panel rather than by the palette component,
 * which loads after the page does, so a press made before it arrives isn't
 * lost. A focused editor that has a use for the shortcut — a rich text
 * field's link command — handles it first and cancels it, so it's left alone
 * then.
 */
export function installCommandPaletteShortcut(): void {
  if (shortcutInstalled) {
    return;
  }

  shortcutInstalled = true;

  window.addEventListener('keydown', (event: KeyboardEvent) => {
    if (
      event.key.toLowerCase() !== 'k' ||
      event.altKey ||
      event.shiftKey ||
      !(event.metaKey || event.ctrlKey) ||
      event.defaultPrevented
    ) {
      return;
    }

    event.preventDefault();

    if (!isCommandPaletteOpen.value) {
      openedWithShortcut.value = true;
    }

    isCommandPaletteOpen.value = !isCommandPaletteOpen.value;
  });
}

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

/** What registering something with the palette returns. */
export interface CommandPaletteRegistration {
  /** Removes what was registered, such as a component's actions when it goes. */
  unregister: () => void;
}

/**
 * Adds actions that depend on the page being viewed. The source is called
 * each time the palette opens, so it can read the page's current state.
 *
 * ```js
 * const {unregister} = CommandPalette.registerActions(() => [...]);
 * ```
 */
export function registerActions(
  source: () => CommandPaletteItem[]
): CommandPaletteRegistration {
  actionSources.push(source);

  return {
    unregister: () => {
      const index = actionSources.indexOf(source);

      if (index !== -1) {
        actionSources.splice(index, 1);
      }
    },
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
 * It receives `item` and `view` props, and `actionsTarget`, a selector for the
 * view header's actions to `<Teleport>` buttons of its own into. It emits
 * `done` (optionally with a `{message, run, redirect, reload}` result) once its
 * work has succeeded, or `back` to return to the search results.
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

/**
 * The palette's API for plugins, published as `CommandPalette` from
 * `@craftcms/cp`. Server-side search providers, pages and actions are
 * registered in PHP instead.
 */
export const CommandPalette = {
  open: openCommandPalette,
  close: closeCommandPalette,
  registerProvider,
  registerActions,
  registerView,
};
