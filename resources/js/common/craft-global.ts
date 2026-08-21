import {Base} from '@craftcms/garnish';
import {compatify, type LegacyMembers} from '@craftcms/garnish/compat';

type CraftClass = typeof Base & {
    ancestor?: unknown;
    extend?: (
        instanceMembers?: LegacyMembers,
        staticMembers?: object
    ) => unknown;
};

function restoreLegacyClassApi(value: unknown): void {
    if (
        typeof value !== 'function' ||
        !value.prototype ||
        !(value.prototype instanceof Base)
    ) {
        return;
    }

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
                staticMembers?: object
            ) {
                const Subclass = compatify(this).extend(
                    instanceMembers,
                    staticMembers
                );
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
export function registerCraftGlobals(globals: Record<string, unknown>): void {
    Object.values(globals).forEach(restoreLegacyClassApi);

    const craft = ((window as any).Craft ??= {});
    Object.assign(craft, globals);
}
