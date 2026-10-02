import {Base} from '@craftcms/garnish';
import {compatify, type LegacyMembers} from '@craftcms/garnish/compat';

type CraftClass = typeof Base & {
  ancestor?: CraftClass;
  extend?: (
    instanceMembers?: LegacyMembers,
    staticMembers?: LegacyMembers
  ) => CraftClass;
};

interface CraftGlobalValue {
  readonly constructor: Function;
}

function restoreLegacyClassApi(value: CraftGlobalValue): void {
  if (
    !(value instanceof Function) ||
    !value.prototype ||
    !(value.prototype instanceof Base)
  ) {
    return;
  }

  // SAFETY: the checks above establish a Base constructor with its prototype.
  const craftClass = value as CraftClass;

  if (!Object.hasOwn(craftClass, 'ancestor')) {
    Object.defineProperty(craftClass, 'ancestor', {
      configurable: true,
      value: Object.getPrototypeOf(craftClass),
      writable: true,
    });
  }

  if (!Object.hasOwn(craftClass, 'extend')) {
    Object.defineProperty(craftClass, 'extend', {
      configurable: true,
      value: function (
        this: CraftClass,
        instanceMembers?: LegacyMembers,
        staticMembers?: LegacyMembers
      ) {
        const Subclass = compatify(this).extend(instanceMembers, staticMembers);
        Subclass.ancestor = this;
        return Subclass;
      },
      writable: true,
    });
  }
}

/**
 * Assigns one or more values onto the legacy `window.Craft` namespace (creating
 * it if it doesn't exist yet), so PHP-emitted markup and the still-legacy cp
 * bundle can reach ported classes via `Craft.*`.
 *
 * Replaces the per-module
 * `const craft = (window as any).Craft ?? ((window as any).Craft = {})`
 * boilerplate the imperative ports were each repeating.
 *
 * @example
 * registerCraftGlobals({FieldToggle});
 * registerCraftGlobals({FormObserver, IntervalManager});
 */
export function registerCraftGlobals(
  globals: Record<string, CraftGlobalValue>
): void {
  Object.values(globals).forEach(restoreLegacyClassApi);

  const craft = Object.assign(window, {Craft: window.Craft ?? {}}).Craft;
  Object.assign(craft, globals);
}
