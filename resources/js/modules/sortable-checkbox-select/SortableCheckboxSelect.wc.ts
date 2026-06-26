import {SortableCheckboxSelect} from '@/modules/sortable-checkbox-select/SortableCheckboxSelect';
import {ControllerElement} from '@/common/web-components';

/**
 * `<craft-sortable-checkbox-select>` — boots a {@link SortableCheckboxSelect}
 * around the server-rendered `.checkbox-select` it wraps, so PHP/Twig can emit the
 * element instead of a manual `new Craft.SortableCheckboxSelect(...)` boot.
 *
 * The control renders as **light-DOM children**: the class queries the
 * `.checkbox-select` container for its `.checkbox-select-item` rows and exposes a
 * jQuery `.data()` back-reference off it (read by the legacy `BaseElementIndex`),
 * so a shadow-DOM component would hide both. The constructor takes only the
 * container — there are no settings to wire as an attribute.
 *
 * The class registers itself in `sortableCheckboxSelectData` (keyed by the
 * `.checkbox-select` element) on boot, so a consumer that looks it up there — like
 * the Card View Designer — would reuse this instance rather than create its own.
 */
export default class CraftSortableCheckboxSelect extends ControllerElement<SortableCheckboxSelect> {
  protected readonly rootSelector = '.checkbox-select';

  protected create(root: HTMLElement): SortableCheckboxSelect {
    return new SortableCheckboxSelect(root);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-sortable-checkbox-select': CraftSortableCheckboxSelect;
  }
}
