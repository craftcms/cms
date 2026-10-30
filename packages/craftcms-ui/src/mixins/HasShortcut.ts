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
   * JSON. Shown and announced, but the host does not bind the key.
   */
  shortcut: string | {alt?: boolean; shift?: boolean; key: string} | null;
  /** {@link shortcut}, normalized, or null without one. */
  readonly shortcutKeys: ShortcutKeys | null;
  /**
   * {@link shortcut} as an `aria-keyshortcuts` value for this platform, such
   * as `Meta+Shift+S` on a Mac or `Control+Shift+S` elsewhere.
   */
  readonly shortcutAriaKeys: string | null;
}

/**
 * Adds a `shortcut` to a Lit element. Components render their own
 * `craft-shortcut` from {@link HasShortcutHost.shortcutKeys}, and announce it
 * with `aria-keyshortcuts` from {@link HasShortcutHost.shortcutAriaKeys}. The
 * host still doesn't bind the key.
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

    get shortcutAriaKeys(): string | null {
      const keys = this.shortcutKeys;

      if (!keys) {
        return null;
      }

      const isMac = /mac|iphone|ipad|ipod/i.test(navigator.platform);

      return [
        isMac ? 'Meta' : 'Control',
        ...(keys.alt ? ['Alt'] : []),
        ...(keys.shift ? ['Shift'] : []),
        keys.key.length === 1 ? keys.key.toUpperCase() : keys.key,
      ].join('+');
    }
  }

  // Cast through named types only, as Actionable does, so Lit's protected
  // members don't leak into consumers' declaration emit.
  return HasShortcutElement as unknown as Constructor<HasShortcutHost> & T;
};
