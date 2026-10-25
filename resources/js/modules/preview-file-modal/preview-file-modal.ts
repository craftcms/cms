import {
  focusReturnFor,
  type FocusReturnResolver,
} from '@craftcms/ui/utilities/focus-return';
import {hasAttr} from '@craftcms/garnish';
import {highlightCodeBlocks} from '@/common/utils/highlight-code';

declare const Craft: any;
declare const $: any;

/** A `craft-dialog`. */
interface PreviewDialog extends HTMLElement {
  opened: boolean;
  noHeader: boolean;
  closeOnOutsideClick: boolean;
  label: string;
}

interface PreviewFileModalSettings {
  minGutter: number;
  startingWidth: number | null;
  startingHeight: number | null;
  [key: string]: unknown;
}

const DEFAULTS: PreviewFileModalSettings = {
  minGutter: 50,
  startingWidth: null,
  startingHeight: null,
};

/** Centers the "No preview available" message in an otherwise empty preview. */
const EMPTY_CLASSES = 'zilch flex items-center justify-center';

/**
 * PreviewFileModal — `Craft.PreviewFileModal`, on a `craft-dialog`. Opens an
 * asset preview via the `assets/preview-file` action, sized to the image when
 * there is one, and supports arrow-key navigation between sibling assets in an
 * element select.
 *
 * Preview templates reach the open preview through
 * `Craft.PreviewFileModal.openInstance`: `$container` is the jQuery-wrapped
 * element holding the preview HTML, and `resizePreviewImage()` fits an
 * image preview to the viewport once it has loaded.
 */
export class PreviewFileModal {
  static openInstance: PreviewFileModal | null = null;

  settings: PreviewFileModalSettings;
  assetId: number | null = null;
  elementSelect: any = null;
  $triggerElement: HTMLElement | null = null;
  $bumperButtonStart: any = null;
  $bumperButtonEnd: any = null;
  dialog: PreviewDialog;
  container: HTMLElement;
  $container: any;
  visible = false;
  requestId = 0;

  /** Finds the opener again, or its menu's button once the menu has closed. */
  #returnFocus: FocusReturnResolver = () => null;
  #destroyed = false;

  constructor(
    assetId: number,
    elementSelectOrSettings?: any,
    settings?: Partial<PreviewFileModalSettings>
  ) {
    // (assetId, settings) overload
    if (settings === undefined && $.isPlainObject(elementSelectOrSettings)) {
      settings = elementSelectOrSettings;
      elementSelectOrSettings = null;
    }

    this.settings = {...DEFAULTS, ...settings};
    this.elementSelect = elementSelectOrSettings ?? null;

    const focused = document.activeElement;
    this.$triggerElement = focused instanceof HTMLElement ? focused : null;
    this.#returnFocus = focusReturnFor(this.$triggerElement);

    PreviewFileModal.openInstance?.quickHide();
    PreviewFileModal.openInstance = this;

    this.dialog = document.createElement('craft-dialog') as PreviewDialog;
    this.dialog.classList.add('previewmodal');
    this.dialog.noHeader = true;
    this.dialog.closeOnOutsideClick = true;
    this.dialog.label = Craft.t('app', 'Preview file');

    this.container = document.createElement('div');
    this.container.className = 'size-full';
    this.$container = $(this.container);
    this.dialog.append(this.container);

    this.$bumperButtonStart = $('<button/>', {
      type: 'button',
      class: 'skip-link top-0 start-0',
      text: Craft.t('app', 'Close Preview'),
    }).on('click', () => this.hide());
    this.$bumperButtonEnd = this.$bumperButtonStart.clone(true);

    this.dialog.addEventListener('keydown', this.#onKeydown);
    this.dialog.addEventListener('craft-after-hide', this.#onAfterHide);
    window.addEventListener('resize', this.#onWindowResize);

    document.body.append(this.dialog);
    this.dialog.opened = true;
    this.visible = true;
    Craft.cp.announce(Craft.t('app', 'Loading'));

    this.loadAsset(
      assetId,
      this.settings.startingWidth,
      this.settings.startingHeight
    );
  }

  getSelectItem(): any {
    const $item = this.elementSelect?.$items.filter(
      `[data-id=${this.assetId}]`
    );
    return $item?.length ? $item : null;
  }

  previewPreviousAsset(): void {
    this.#previewSibling('getPreviousItem');
  }

  previewNextAsset(): void {
    this.#previewSibling('getNextItem');
  }

  #previewSibling(getter: 'getPreviousItem' | 'getNextItem'): void {
    const $element = this.getSelectItem();
    if (!$element) return;

    const index = this.elementSelect.getItemIndex($element);
    const $sibling = this.elementSelect[getter](index);
    if (
      $sibling?.length &&
      PreviewFileModal.showForAsset($sibling, this.elementSelect)
    ) {
      this.elementSelect.deselectAll();
      this.elementSelect.selectItem($sibling, false, false);
    }
  }

  /** Closes the preview, returning focus to the asset or whatever opened it. */
  hide(): void {
    if (!this.visible) return;
    this.visible = false;
    this.dialog.opened = false;
  }

  /** Closes the preview immediately, without returning focus. */
  quickHide(): void {
    this.visible = false;
    this.destroy();
  }

  /** @deprecated */
  selfDestruct(): boolean {
    this.quickHide();
    return true;
  }

  destroy(): void {
    if (this.#destroyed) return;
    this.#destroyed = true;
    this.visible = false;

    this.dialog.removeEventListener('keydown', this.#onKeydown);
    this.dialog.removeEventListener('craft-after-hide', this.#onAfterHide);
    window.removeEventListener('resize', this.#onWindowResize);
    this.dialog.remove();

    if (PreviewFileModal.openInstance === this) {
      PreviewFileModal.openInstance = null;
      // SAFETY: The focal point bundle owns the optional imageFocalPoint global.
      (window as any).imageFocalPoint?.destruct();
      delete (window as any).imageFocalPoint;
    }
  }

  loadAsset(
    assetId: number,
    startingWidth?: number | null,
    startingHeight?: number | null
  ): void {
    this.assetId = assetId;
    this.$container.empty().removeClass(EMPTY_CLASSES);

    const [width, height] =
      startingWidth && startingHeight
        ? this.#fit(startingWidth, startingHeight)
        : this.#defaultSize();
    this._resizeContainer(width, height);

    const spinner = document.createElement('craft-spinner');
    spinner.className = 'absolute inset-0 m-auto size-fit';
    this.container.append(spinner);

    this.requestId++;
    const requestId = this.requestId;

    const onResponse = () => {
      spinner.remove();
    };

    Craft.sendActionRequest('POST', 'assets/preview-file', {
      data: {assetId, requestId},
    })
      .then(async (response: any) => {
        if (response.data.requestId !== this.requestId) return;
        onResponse();

        if (!response.data.previewHtml) {
          this.$container
            .addClass(EMPTY_CLASSES)
            .append($('<p/>', {text: Craft.t('app', 'No preview available.')}));
          this._addBumperButtons();
          return;
        }

        this.container.dataset.assetId = String(assetId);
        this.$container.append(response.data.previewHtml);
        highlightCodeBlocks(this.container);
        this._addBumperButtons();
        await Craft.appendHeadHtml(response.data.headHtml);
        await Craft.appendBodyHtml(response.data.bodyHtml);
      })
      .catch(({response}: any) => {
        if (requestId !== this.requestId) return;
        onResponse();
        Craft.cp.displayError(response?.data?.message);
        this.hide();
      });
  }

  _addBumperButtons(): void {
    this.$container
      .prepend(this.$bumperButtonStart)
      .append(this.$bumperButtonEnd);
  }

  _resizeContainer(width: number, height: number): void {
    for (const prop of ['inline-size', 'min-inline-size', 'max-inline-size']) {
      this.dialog.style.setProperty(`--c-dialog-${prop}`, `${width}px`);
    }
    for (const prop of ['block-size', 'max-block-size']) {
      this.dialog.style.setProperty(`--c-dialog-${prop}`, `${height}px`);
    }
  }

  /** A 4:3 box two thirds the viewport's height, within the gutters. */
  #defaultSize(): [number, number] {
    const width = Math.min(
      ((window.innerHeight * 0.66) / 3) * 4,
      window.innerWidth - this.settings.minGutter * 2
    );
    return [Math.round(width), Math.round((width / 4) * 3)];
  }

  /** Scales `width` × `height` down, never up, to fit within the gutters. */
  #fit(width: number, height: number): [number, number] {
    const gutter = this.settings.minGutter * 2;
    const scale = Math.min(
      1,
      (window.innerWidth - gutter) / width,
      (window.innerHeight - gutter) / height
    );
    return [Math.round(width * scale), Math.round(height * scale)];
  }

  #onKeydown = (ev: KeyboardEvent): void => {
    switch (ev.key) {
      case 'ArrowLeft':
      case 'ArrowUp':
        if (this.getSelectItem()) {
          ev.preventDefault();
          this.previewPreviousAsset();
        }
        break;
      case 'ArrowRight':
      case 'ArrowDown':
        if (this.getSelectItem()) {
          ev.preventDefault();
          this.previewNextAsset();
        }
        break;
      case ' ':
        if (ev.shiftKey) {
          ev.preventDefault();
          this.hide();
        }
        break;
    }
  };

  #onAfterHide = (ev: Event): void => {
    if (ev.target !== this.dialog) return;

    const $element = this.getSelectItem();
    this.destroy();

    if ($element) {
      this.elementSelect.focusItem($element);
    } else {
      this.#returnFocus()?.focus();
    }
  };

  #onWindowResize = (): void => {
    if (this.container.querySelector('img[data-maxwidth]')) {
      PreviewFileModal.resizePreviewImage();
    }
  };

  /** Fits the open image preview, and the dialog around it, to the viewport. */
  static resizePreviewImage(): void {
    const instance = PreviewFileModal.openInstance;
    const img =
      instance?.container.querySelector<HTMLImageElement>('img[data-maxwidth]');
    if (!instance || !img) return;

    const [width, height] = instance.#fit(
      Number(img.dataset.maxwidth),
      Number(img.dataset.maxheight)
    );
    img.style.width = `${width}px`;
    img.style.height = `${height}px`;
    instance._resizeContainer(width, height);

    // SAFETY: The focal point bundle owns the optional imageFocalPoint global.
    (window as any).imageFocalPoint?.renderFocal();
  }

  static showForAsset($element: any, elementSelect?: any): boolean {
    if (!$element.hasClass('element')) {
      $element = $element.find('.element:first');
    }
    if (
      !$element.hasClass('element') ||
      hasAttr($element[0], 'data-folder-id')
    ) {
      return false;
    }

    const settings: Partial<PreviewFileModalSettings> = {};
    if ($element.data('image-width')) {
      settings.startingWidth = $element.data('image-width');
      settings.startingHeight = $element.data('image-height');
    }

    const instance = PreviewFileModal.openInstance;
    if (
      instance?.visible &&
      instance.elementSelect === (elementSelect ?? null)
    ) {
      instance.loadAsset(
        $element.data('id'),
        settings.startingWidth,
        settings.startingHeight
      );
      return true;
    }

    new PreviewFileModal($element.data('id'), elementSelect, settings);
    return true;
  }
}
