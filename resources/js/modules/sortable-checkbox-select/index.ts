import {Base} from '@craftcms/garnish';
import '@craftcms/ui/components/checkbox-select/checkbox-select';
import type CraftCheckboxSelect from '@craftcms/ui/components/checkbox-select/checkbox-select';
import {registerCraftGlobals} from '@/common/craft-global';

declare const $: any;

/**
 * Turns a legacy `.cp-checkbox-select` container into a
 * `<craft-checkbox-select>`, keeping its attributes and items.
 */
function upgrade(container: HTMLElement): CraftCheckboxSelect {
  if (container.localName === 'craft-checkbox-select') {
    // SAFETY: Checked by tag name above.
    return container as CraftCheckboxSelect;
  }

  const element = document.createElement('craft-checkbox-select');
  for (const {name, value} of Array.from(container.attributes)) {
    element.setAttribute(name, value);
  }
  element.append(...Array.from(container.childNodes));
  container.replaceWith(element);

  return element;
}

/**
 * @deprecated in 6.0.0. Use `<craft-checkbox-select sortable
 * presentation="add-menu">`, which fires `craft-order-change`.
 *
 * Kept for code that still calls `new Craft.SortableCheckboxSelect($container)`:
 * it upgrades the container to the element, and re-triggers its moves as
 * `sortChange`.
 */
export class SortableCheckboxSelect extends Base {
  $container: any;
  element: CraftCheckboxSelect;

  constructor(container: any) {
    super();
    this.element = upgrade($(container)[0]);
    this.element.sortable = true;
    this.element.presentation = 'add-menu';
    this.element.addEventListener('craft-order-change', this.#onOrderChange);
    this.$container = $(this.element);
    this.$container.data('sortableCheckboxSelect', this);
  }

  #onOrderChange = (): void => {
    this.trigger('sortChange');
  };

  override destroy(): void {
    this.element.removeEventListener('craft-order-change', this.#onOrderChange);
    this.$container.removeData('sortableCheckboxSelect');
    super.destroy();
  }
}

registerCraftGlobals({SortableCheckboxSelect});
