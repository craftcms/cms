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
  const craft = ((window as any).Craft ??= {});
  Object.assign(craft, globals);
}
