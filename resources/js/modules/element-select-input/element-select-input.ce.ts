import {ControllerElement} from '@/common/web-components';
import {AssetSelectInput} from '@/modules/asset-select-input/asset-select-input';
import {BaseElementSelectInput} from './base-element-select-input';
import {EntrySelectInput} from './entry-select-input';

declare const Craft: any;

type ElementSelectInputConstructor = new (
  settings?: Record<string, any>
) => BaseElementSelectInput;

export default class CraftElementSelectInput extends ControllerElement<BaseElementSelectInput> {
  protected readonly rootSelector = ':scope > .elements';
  protected readonly defaultInputClass: ElementSelectInputConstructor =
    BaseElementSelectInput;

  protected create(): BaseElementSelectInput {
    Craft.initUiElements(this);

    const Input = this.#inputClass();

    return new Input({...this.jsonAttr('settings'), id: this.id});
  }

  get selectedIds(): number[] {
    return (
      this.instance?.getSelectedElementIds() ??
      [...this.querySelectorAll<HTMLElement>(':scope > .elements .element')]
        .map((element) => Number(element.dataset.id))
        .filter(Number.isFinite)
    );
  }

  /**
   * Opens the element selector modal to replace the chip with this ID —
   * the "Replace" chip action.
   *
   * Along with {@link removeElement}, this is the seam for hosts that render the
   * chips' action menus themselves (`ElementSelectControl.vue`) instead of
   * letting `Craft.addActionsToChip()` inject them: `instance` is protected, so
   * the menu items talk to the custom element rather than the controller.
   */
  replaceElement(id: number | string): void {
    const $chip = this.#chip(id);
    if ($chip) {
      this.instance?.showReplaceModal($chip);
    }
  }

  /**
   * Removes the chip with this ID — or, when it's part of the current
   * multi-selection, the whole selection, as the "Remove" chip action does.
   *
   * Named for the controller method it fronts, not `remove()`: that's
   * `Element.remove()`, and overriding it would break every DOM consumer.
   */
  removeElement(id: number | string): void {
    const $chip = this.#chip(id);
    if ($chip) {
      this.instance?.removeElementOrSelection($chip);
    }
  }

  /**
   * The controller's jQuery-wrapped chip for `id`, or `null` when it isn't
   * booted yet or doesn't know that chip — its methods take jQuery objects, and
   * `$elements` is the collection they operate on.
   */
  #chip(id: number | string): any {
    const $elements = this.instance?.$elements;

    if (!$elements?.length) {
      return null;
    }

    const $chip = $elements.filter(`[data-id="${id}"]`);

    return $chip.length ? $chip : null;
  }

  #inputClass(): ElementSelectInputConstructor {
    const name = this.getAttribute('input-class');
    if (!name) {
      return this.defaultInputClass;
    }

    const Input = this.#globalInputClass(name);

    if (typeof Input !== 'function') {
      throw new Error(`Unknown element select input class [${name}].`);
    }

    return Input;
  }

  #globalInputClass(name: string): ElementSelectInputConstructor | undefined {
    return name
      .split('.')
      .reduce<any>((value, segment) => value?.[segment], window);
  }
}

export class CraftEntrySelectInput extends CraftElementSelectInput {
  protected override readonly defaultInputClass = EntrySelectInput;
}

export class CraftAssetSelectInput extends CraftElementSelectInput {
  protected override readonly defaultInputClass = AssetSelectInput;
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-asset-select-input': CraftAssetSelectInput;
    'craft-element-select-input': CraftElementSelectInput;
    'craft-entry-select-input': CraftEntrySelectInput;
  }
}
