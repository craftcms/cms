import type {SortableCheckboxSelect} from './SortableCheckboxSelect';

/**
 * Native replacement for the legacy jQuery `$container.data('sortableCheckboxSelect')`
 * object back-reference, mirroring how `@craftcms/garnish` (and the FLD /
 * EditableTable ports) replaced `$.data` with a module-level `WeakMap` keyed by
 * the element.
 *
 * The class still sets the jQuery `.data()` too, because the still-legacy
 * `BaseElementIndex` reads the instance back that way; modern consumers (the
 * Card View Designer) read this map instead. See the module README.
 */
export const sortableCheckboxSelectData = new WeakMap<
  Element,
  SortableCheckboxSelect
>();
