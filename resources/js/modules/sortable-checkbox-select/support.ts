import type {SortableCheckboxSelect} from './sortable-checkbox-select';

/**
 * WeakMap replacing the legacy jQuery `$container.data('sortableCheckboxSelect')`
 * back-reference (matching the garnish/FLD/EditableTable pattern). The class still
 * sets `.data()` too, since the legacy `BaseElementIndex` reads it that way; modern
 * consumers (the Card View Designer) read this map. See the module README.
 */
export const sortableCheckboxSelectData = new WeakMap<
  Element,
  SortableCheckboxSelect
>();
