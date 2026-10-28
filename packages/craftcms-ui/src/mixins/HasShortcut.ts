import type {LitElement} from 'lit';
import {property} from 'lit/decorators.js';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** A key, and the modifiers pressed with it besides Cmd/Ctrl. */
export interface ShortcutKeys {
  key: string;
  alt: boolean;
  shift: boolean;
}

/** The shortcut surface the mixin adds to a host element. */
export interface HasShortcutHost {
  /**
   * A keyboard shortcut shown on the host. Either a plain key (`"S"`,
   * `"ctrl+k"`) or an object naming the modifiers, as an object or as its
   * JSON. Display only — the host does not bind the key.
   */
  shortcut: string | {alt?: boolean; shift?: boolean; key: string} | null;
  /** {@link shortcut}, normalized, or null without one. */
  readonly shortcutKeys: ShortcutKeys | null;
}

/**
 * Adds a display-only `shortcut` to a Lit element. Components render their
 * own `craft-shortcut` from {@link HasShortcutHost.shortcutKeys}.
 *
 * The JSON form is parsed on read rather than by an attribute converter: Vue's
 * in-DOM compiler sets attribute values as string properties on an upgraded
 * element, which would skip a converter.
 *
 * The analyzer does not follow members through a mixin, so a host documents
 * `shortcut` with `@attr shortcut` on its own class docblock.
 */
export const HasShortcut = <T extends Constructor<LitElement>>(Base: T) => {
  class HasShortcutElement extends Base {
    @property() shortcut: HasShortcutHost['shortcut'] = null;

    get shortcutKeys(): ShortcutKeys | null {
      let shortcut = this.shortcut;

      if (typeof shortcut === 'string' && shortcut.trim().startsWith('{')) {
        try {
          shortcut = JSON.parse(shortcut);
        } catch {
          // Not JSON — show it as a plain key
        }
      }

      if (!shortcut) {
        return null;
      }

      if (typeof shortcut === 'string') {
        return {key: shortcut, alt: false, shift: false};
      }

      return {
        key: shortcut.key,
        alt: shortcut.alt ?? false,
        shift: shortcut.shift ?? false,
      };
    }
  }

  // Cast through named types only, as Actionable does, so Lit's protected
  // members don't leak into consumers' declaration emit.
  return HasShortcutElement as unknown as Constructor<HasShortcutHost> & T;
};
