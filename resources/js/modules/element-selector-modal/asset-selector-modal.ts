import {BaseElementSelectorModal} from './base-element-selector-modal';

declare const Craft: any;
declare const $: any;
declare const Garnish: any;

const DEFAULTS = {
  canSelectImageTransforms: false,
  // SAFETY: The empty default establishes this modal's transform descriptor list.
  transforms: [] as Array<{handle: string; name: string}>,
};

/**
 * AssetSelectorModal — a port of `Craft.AssetSelectorModal` onto
 * {@link BaseElementSelectorModal}. Adds an optional "Select transform" menu
 * button (using `Garnish.MenuBtn`) that lets the user pick an image transform
 * before selecting assets.
 *
 * Static `transformUrls` is preserved as a cross-instance URL cache, matching
 * the legacy class static property.
 */
export class AssetSelectorModal extends BaseElementSelectorModal {
  // SAFETY: These defaults extend the base modal settings without changing existing keys.
  static override defaults =
    DEFAULTS as typeof BaseElementSelectorModal.defaults & typeof DEFAULTS;

  $selectTransformBtn: any = null;
  _selectedTransform: string | null = null;

  /** Cross-instance cache of transform URL lookups keyed by `[transform][elementId]`. */
  static transformUrls: Record<string, Record<number, string | false>> = {};

  constructor(elementType: string, settings?: any) {
    super(elementType, settings);
    if (new.target === AssetSelectorModal) {
      this.init(elementType, settings);
    }
  }

  override init(elementType: string, settings?: any): void {
    settings = Object.assign({}, AssetSelectorModal.defaults, settings);
    super.init(elementType, settings);

    if (settings.transforms?.length) {
      this.createSelectTransformButton(settings.transforms);
    }
  }

  createSelectTransformButton(
    transforms: Array<{handle: string; name: string}>
  ): void {
    if (!transforms?.length) return;

    const $btnGroup = $('<div class="btngroup"/>').appendTo(
      this.$primaryButtons
    );
    this.$selectBtn.appendTo($btnGroup);

    this.$selectTransformBtn = $('<button/>', {
      type: 'button',
      class: 'btn menubtn disabled',
      text: Craft.t('app', 'Select transform'),
    }).appendTo($btnGroup);

    const $menu = $('<div class="menu" data-align="right"></div>').insertAfter(
      this.$selectTransformBtn
    );
    const $menuList = $('<ul></ul>').appendTo($menu);

    for (const transform of transforms) {
      $(
        `<li><a data-transform="${transform.handle}">${transform.name}</a></li>`
      ).appendTo($menuList);
    }

    const menuButton = new Garnish.MenuBtn(this.$selectTransformBtn, {
      onOptionSelect: (option: Element) => this.onSelectTransform(option),
    });
    menuButton.disable();

    this.$selectTransformBtn.data('menuButton', menuButton);
  }

  override onSelectionChange(): void {
    const $selectedElements = this.elementIndex?.getSelectedElements();
    const allowTransforms =
      !!$selectedElements?.length && !!this.settings.transforms?.length;

    const menuBtn = this.$selectTransformBtn?.data('menuButton') ?? null;

    if (allowTransforms) {
      menuBtn?.enable();
      this.$selectTransformBtn?.removeClass('disabled');
    } else if (this.$selectTransformBtn) {
      menuBtn?.disable();
      this.$selectTransformBtn.addClass('disabled');
    }

    super.onSelectionChange();
  }

  onSelectTransform(option: Element): void {
    // SAFETY: Transform menu items are rendered above with string data-transform values.
    const transform = $(option).data('transform') as string;
    this.selectImagesWithTransform(transform);
  }

  selectImagesWithTransform(transform: string): void {
    if (AssetSelectorModal.transformUrls[transform] === undefined) {
      AssetSelectorModal.transformUrls[transform] = {};
    }

    const $selectedElements = this.elementIndex.getSelectedElements();
    const missingIds: number[] = [];

    for (let i = 0; i < $selectedElements.length; i++) {
      const $item = $($selectedElements[i]);
      const elementId: number = Craft.getElementInfo($item).id;
      if (
        AssetSelectorModal.transformUrls[transform][elementId] === undefined
      ) {
        missingIds.push(elementId);
      }
    }

    if (missingIds.length) {
      this.showFooterSpinner();
      this.fetchMissingTransformUrls(missingIds, transform, () => {
        this.hideFooterSpinner();
        this.selectImagesWithTransform(transform);
      });
    } else {
      this._selectedTransform = transform;
      this.selectElements();
      this._selectedTransform = null;
    }
  }

  fetchMissingTransformUrls(
    ids: number[],
    transform: string,
    callback: () => void
  ): void {
    const elementId = ids.pop()!;
    // Ensure the transform bucket exists (may have been created in
    // selectImagesWithTransform(), but guard here for safety).
    AssetSelectorModal.transformUrls[transform] ??= {};
    const bucket = AssetSelectorModal.transformUrls[transform]!;

    Craft.sendActionRequest('POST', 'assets/generate-transform', {
      data: {assetId: elementId, handle: transform},
    })
      .then((response: any) => {
        bucket[elementId] = response.data.url || false;
      })
      .catch(() => {
        bucket[elementId] = false;
      })
      .finally(() => {
        if (ids.length) {
          this.fetchMissingTransformUrls(ids, transform, callback);
        } else {
          callback();
        }
      });
  }

  override getElementInfo($selectedElements: any): any[] {
    const info = super.getElementInfo($selectedElements);

    if (this._selectedTransform) {
      const cache =
        AssetSelectorModal.transformUrls[this._selectedTransform] ?? {};
      for (const item of info) {
        const url = cache[Number(item.id)];
        if (url !== undefined && url !== false) {
          item.url = url;
        }
      }
    }

    return info;
  }

  override onSelect(elementInfo: any): void {
    this.settings.onSelect(elementInfo, this._selectedTransform);
  }
}
