import type {NestedElementManager} from './nested-element-manager';

/**
 * WeakMap replacing the legacy jQuery `.data('nestedElementManager')`
 * back-reference (the same pattern as component-select /
 * grouped-entry-type-manager). Keyed by the manager's container — the
 * server-rendered cards/index `div`, not the `<craft-nested-element-manager>`
 * wrapper — since that's the element the legacy back-reference lived on and
 * what a `new Craft.NestedElementManager(container, …)` boot passes.
 */
export const nestedElementManagerData = new WeakMap<
  Element,
  NestedElementManager
>();
