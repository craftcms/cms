import type {GroupedEntryTypeManager} from './grouped-entry-type-manager';

/**
 * WeakMap replacing the legacy jQuery `$container.data('entryTypeManager')`
 * back-reference (matching the sortable-checkbox-select / garnish pattern).
 * Keyed by the manager's container element; the chips' "Move to previous/next
 * group" actions resolve their manager through it at activation time (see
 * `handleDefineChipActions`), which is what lets a chip keep working after its
 * manager is destroyed and re-booted. See the module README.
 */
export const groupedEntryTypeManagerData = new WeakMap<
  Element,
  GroupedEntryTypeManager
>();
