import type {ComponentSelect} from './component-select';

/**
 * WeakMap replacing what would otherwise be a jQuery `.data('componentSelect')`
 * back-reference (matching the grouped-entry-type-manager /
 * sortable-checkbox-select pattern). Keyed by the `<craft-component-select>`
 * element (the controller's `container`); a chip's action-menu items and its
 * `<craft-reorder-button>` resolve their *owning* select through it at event
 * time — via `closestRegistered` — so a chip adopted by a different select
 * (see `ComponentSelect.adoptChip`) keeps working without being re-wired. See
 * the module README.
 */
export const componentSelectData = new WeakMap<Element, ComponentSelect>();
