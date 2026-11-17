import {
  getHotkeyManager,
  parseHotkey,
  type ParsedHotkeyChord,
  type RegisterableHotkeyChord,
  type Target,
} from '@tanstack/hotkeys';
import {onScopeDispose} from 'vue';

declare module '@tanstack/hotkeys' {
  interface HotkeyMeta {
    craft?: Pick<ShortcutOptions, 'description' | 'group' | 'isAvailable'>;
  }
}

export interface ShortcutOptions {
  /**
   * What the shortcut does, for the command palette's “Keyboard shortcuts”
   * list. Shortcuts without one aren't listed.
   */
  description?: Label;
  /** The list's heading to show it under. Shortcuts without one lead the list. */
  group?: Label;
  /**
   * Where it listens: the document by default, or an element, which only
   * hears keys pressed while focus is inside it.
   */
  target?: Target | null;
  /**
   * Whether it works right now, checked on each press and each time the list
   * opens. A press it doesn't take is left for other handlers.
   */
  isAvailable?: () => boolean;
  /**
   * Whether keys typed into text fields and keys that focused controls use
   * themselves are skipped. Defaults to TanStack Hotkeys' `'auto'`: skipped
   * for single keys and Shift/Alt chords, heard for Mod chords and Escape.
   */
  ignoreInputs?: boolean | 'auto';
  /**
   * `'always'` also prevents the browser's default while the shortcut isn't
   * available, such as Save's on a read-only screen.
   */
  preventDefault?: 'always';
  /**
   * Keeps a press the shortcut takes from other listeners, such as a library's
   * own handler for the same keys. Defaults to `false`.
   */
  stopPropagation?: boolean;
}

/** Text, or a function returning it, for text translated after registering. */
type Label = string | (() => string);

function resolve(label: Label | undefined): string | undefined {
  return typeof label === 'function' ? label() : label;
}

/**
 * Runs when the shortcut is pressed. Returning `false` leaves the press alone,
 * for a shortcut that only applies to some targets.
 */
export type ShortcutCallback = (event: KeyboardEvent) => void | false;

export interface ShortcutRegistration {
  unregister: () => void;
}

/**
 * Registers a keyboard shortcut, such as `'Mod+S'` (Command on Apple
 * platforms, Ctrl elsewhere) or `'Escape'`.
 *
 * A press that a focused editor has already claimed with `preventDefault()`,
 * such as a rich text field's link command on Mod+K, is left alone. Otherwise
 * the shortcut runs the callback and prevents the browser's default. Other
 * listeners still receive the key.
 */
export function registerShortcut(
  hotkey: RegisterableHotkeyChord,
  callback: ShortcutCallback,
  options: ShortcutOptions = {}
): ShortcutRegistration {
  const handle = getHotkeyManager().register(
    hotkey,
    (event) => {
      if (event.defaultPrevented) {
        return;
      }

      if (options.isAvailable?.() === false) {
        if (options.preventDefault === 'always') {
          event.preventDefault();
        }

        return;
      }

      if (callback(event) !== false) {
        event.preventDefault();

        if (options.stopPropagation) {
          event.stopPropagation();
        }
      }
    },
    {
      target: options.target ?? document,
      ignoreInputs: options.ignoreInputs ?? 'auto',
      preventDefault: false,
      stopPropagation: false,
      conflictBehavior: 'allow',
      meta: {
        craft: {
          description: options.description,
          group: options.group,
          isAvailable: options.isAvailable,
        },
      },
    }
  );

  return {unregister: () => handle.unregister()};
}

/** {@link registerShortcut}, unregistered when the component or scope goes. */
export function useShortcut(
  hotkey: RegisterableHotkeyChord,
  callback: ShortcutCallback,
  options: ShortcutOptions = {}
): void {
  onScopeDispose(registerShortcut(hotkey, callback, options).unregister);
}

function isShown(element: Element): boolean {
  return element.isConnected && (element.checkVisibility?.() ?? true);
}

export interface ListedShortcut {
  keys: ParsedHotkeyChord;
  description: string;
  group?: string;
}

/**
 * The described shortcuts that would work where focus was: enabled, in an
 * active scope, available, and listening on the document or an element that's
 * shown. One per shortcut and description.
 */
export function listedShortcuts(): ListedShortcut[] {
  const listed = new Map<string, ListedShortcut>();

  for (const registration of getHotkeyManager().registrations.state.values()) {
    const {isAvailable, ...labels} = registration.options.meta?.craft ?? {};
    const description = resolve(labels.description);

    if (
      !description ||
      registration.kind !== 'chord' ||
      registration.options.enabled === false ||
      !getHotkeyManager().isScopeActive(registration.options.scope) ||
      (registration.target instanceof Element &&
        !isShown(registration.target)) ||
      isAvailable?.() === false
    ) {
      continue;
    }

    const keys = parseHotkey(registration.hotkey);
    const id = `${JSON.stringify(keys)}|${description}`;

    if (!listed.has(id)) {
      listed.set(id, {keys, description, group: resolve(labels.group)});
    }
  }

  return [...listed.values()];
}
