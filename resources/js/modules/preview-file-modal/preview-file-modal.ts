import {
  Modal,
  LEFT_KEY,
  RIGHT_KEY,
  UP_KEY,
  DOWN_KEY,
  SPACE_KEY,
  hasAttr,
  getFocusedElement,
  bod,
} from '@craftcms/garnish';

declare const Craft: any;
declare const $: any;
declare const Garnish: any;

const DEFAULTS = {
  // SAFETY: These nullable dimensions are populated with measured numbers before arithmetic.
  minGutter: 50,
  // SAFETY: The starting width is nullable until the preview payload supplies it.
  startingWidth: null as number | null,
  // SAFETY: The starting height is nullable until the preview payload supplies it.
  startingHeight: null as number | null,
  resizable: true,
};

/**
 * PreviewFileModal — a port of `Craft.PreviewFileModal` onto the modern
 * `@craftcms/garnish` `Modal`. Opens a full-screen asset preview via the
 * `assets/preview-file` action and supports arrow-key navigation between
 * sibling assets in an element select.
 *
 * Notes:
 * - Uses `quickShow()` to skip the fade-in animation (legacy behaviour).
 * - `#$container` is the jQuery reference; `this.$container` (DOM element from
 *   `Modal`) is used only for `setContainer()`.
 * - Static methods `resizePreviewImage` and `showForAsset` are preserved for
 *   external callers (registered on `Craft.PreviewFileModal` via `registerCraftGlobals`).
 */
export class PreviewFileModal extends Modal {
  static openInstance: PreviewFileModal | null = null;

  declare settings: any;

  assetId: number | null = null;
  $spinner: any = null;
  override $triggerElement: any = null;
  $bumperButtonStart: any = null;
  $bumperButtonEnd: any = null;
  override $liveRegion: any = null;
  elementSelect: any = null;
  type: any = null;
  loaded: boolean | null = null;
  requestId = 0;

  #$container: any = null;

  constructor(assetId: number, elementSelectOrSettings?: any, settings?: any) {
    // (assetId, settings) overload
    if (settings === undefined && $.isPlainObject(elementSelectOrSettings)) {
      settings = elementSelectOrSettings;
      elementSelectOrSettings = null;
    }

    // SAFETY: The legacy Modal base accepts an omitted container during deferred construction.
    super(undefined as any, {autoShow: false, resizable: true});

    this.settings = Object.assign({}, DEFAULTS, settings);
    this.elementSelect = elementSelectOrSettings ?? null;
    this.$triggerElement = getFocusedElement();
    this.$liveRegion = $('<span class="visually-hidden" role="status"></span>');

    if (PreviewFileModal.openInstance) {
      PreviewFileModal.openInstance.quickHide();
    }
    PreviewFileModal.openInstance = this;

    this.#$container = $('<div class="modal previewmodal loading"/>').appendTo(
      $(bod)
    );
    this.setContainer(this.#$container[0]);

    Craft.cp.announce(Craft.t('app', 'Loading'));
    this.quickShow();

    Garnish.setFocusWithin(this.#$container[0]);

    this.$bumperButtonStart = Craft.ui.createButton({
      html: Craft.t('app', 'Close Preview'),
      class: 'skip-link',
    });
    this.addListener(this.$bumperButtonStart[0], 'click', () => {
      this.hide();
    });
    this.$bumperButtonEnd = this.$bumperButtonStart.clone(true);

    this.loadAsset(
      assetId,
      this.settings.startingWidth,
      this.settings.startingHeight
    );

    // SAFETY: The keydown listener always receives a native KeyboardEvent.
    this.addListener(this.#$container[0], 'keydown', ((ev: KeyboardEvent) => {
      switch (ev.keyCode) {
        case LEFT_KEY:
        case UP_KEY:
          ev.preventDefault();
          this.previewPreviousAsset();
          break;
        case RIGHT_KEY:
        case DOWN_KEY:
          ev.preventDefault();
          this.previewNextAsset();
          break;
        case SPACE_KEY:
          ev.preventDefault();
          if (ev.shiftKey) {
            this.hide();
          }
          break;
      }
    }) as any);
  }

  getSelectItem(): any {
    const $item = this.elementSelect?.$items.filter(
      `[data-id=${this.assetId}]`
    );
    return $item?.length ? $item : null;
  }

  previewPreviousAsset(): void {
    const $element = this.getSelectItem();
    if ($element) {
      const index = this.elementSelect.getItemIndex($element);
      const $prev = this.elementSelect.getPreviousItem(index);
      if ($prev?.length) {
        if (PreviewFileModal.showForAsset($prev, this.elementSelect)) {
          this.elementSelect.deselectAll();
          this.elementSelect.selectItem($prev, false, false);
        }
      }
    }
  }

  previewNextAsset(): void {
    const $element = this.getSelectItem();
    if ($element) {
      const index = this.elementSelect.getItemIndex($element);
      const $next = this.elementSelect.getNextItem(index);
      if ($next?.length) {
        if (PreviewFileModal.showForAsset($next, this.elementSelect)) {
          this.elementSelect.deselectAll();
          this.elementSelect.selectItem($next, false, false);
        }
      }
    }
  }

  override onFadeOut(): void {
    super.onFadeOut();
    const $element = this.getSelectItem();
    if ($element) {
      this.elementSelect.focusItem($element);
    } else if (this.$triggerElement?.length) {
      this.$triggerElement.focus();
    }
    this.destroy();
  }

  _addBumperButtons(): void {
    this.#$container
      .prepend(this.$bumperButtonStart)
      .append(this.$bumperButtonEnd);
  }

  _addModalName(): void {
    const headingId = 'preview-heading';
    $('<h1/>', {
      class: 'visually-hidden',
      id: headingId,
      text: Craft.t('app', 'Preview file'),
    }).prependTo(this.#$container);
    this.#$container.attr('aria-labelledby', headingId);
  }

  _addLiveRegion(): void {
    this.#$container.append(this.$liveRegion);
  }

  /** @deprecated */
  selfDestruct(): boolean {
    this.quickHide();
    return true;
  }

  override destroy(): void {
    super.destroy();
    if (PreviewFileModal.openInstance === this) {
      PreviewFileModal.openInstance = null;
      Craft.focalPoint?.destruct();
      Craft.focalPoint = null;
    }
  }

  loadAsset(
    assetId: number,
    startingWidth?: number | null,
    startingHeight?: number | null
  ): void {
    this.assetId = assetId;
    this.#$container.empty();
    this.loaded = false;
    this.desiredHeight = null;
    this.desiredWidth = null;

    let containerHeight = $(window).height() * 0.66;
    let containerWidth = Math.min(
      (containerHeight / 3) * 4,
      $(window).width() - this.settings.minGutter * 2
    );
    containerHeight = (containerWidth / 4) * 3;

    if (startingWidth && startingHeight) {
      const ratio = startingWidth / startingHeight;
      containerWidth = Math.min(
        startingWidth,
        $(window).width() - this.settings.minGutter * 2
      );
      containerHeight = Math.min(
        containerWidth / ratio,
        $(window).height() - this.settings.minGutter * 2
      );
      containerWidth = containerHeight * ratio;

      if (
        containerWidth >
        Math.min(startingWidth, $(window).width() - this.settings.minGutter * 2)
      ) {
        containerWidth = Math.min(
          startingWidth,
          $(window).width() - this.settings.minGutter * 2
        );
        containerHeight = containerWidth / ratio;
      }
    }

    this._resizeContainer(containerWidth, containerHeight);

    this.$spinner = $('<div class="spinner centeralign"></div>').appendTo(
      this.#$container
    );
    const top =
      this.#$container.height() / 2 - this.$spinner.height() / 2 + 'px';
    const left =
      this.#$container.width() / 2 - this.$spinner.width() / 2 + 'px';
    this.$spinner.css({left, top, position: 'absolute'});

    this.requestId++;
    const requestId = this.requestId;

    const onResponse = () => {
      this.#$container.removeClass('loading');
      this.$spinner.remove();
      this.loaded = true;
    };

    Craft.sendActionRequest('POST', 'assets/preview-file', {
      data: {assetId, requestId},
    })
      .then(async (response: any) => {
        onResponse();
        if (response.data.requestId !== this.requestId) return;

        if (!response.data.previewHtml) {
          this.#$container.addClass('zilch');
          this.#$container.append(
            $('<p/>', {text: Craft.t('app', 'No preview available.')})
          );
          this._addBumperButtons();
          return;
        }

        this.#$container.removeClass('zilch');
        this.#$container.attr('data-asset-id', this.assetId);
        this.#$container.append(response.data.previewHtml);
        this._addBumperButtons();
        this._addLiveRegion();
        this._addModalName();
        await Craft.appendHeadHtml(response.data.headHtml);
        await Craft.appendBodyHtml(response.data.bodyHtml);
      })
      .catch(({response}: any) => {
        onResponse();
        Craft.cp.displayError(response?.data?.message);
        this.hide();
      });
  }

  _resizeContainer(containerWidth: number, containerHeight: number): void {
    this.#$container.css({
      width: containerWidth,
      'min-width': containerWidth,
      'max-width': containerWidth,
      height: containerHeight,
      'min-height': containerHeight,
      'max-height': containerHeight,
      top: ($(window).height() - containerHeight) / 2,
      left: ($(window).width() - containerWidth) / 2,
    });
  }

  static resizePreviewImage(): void {
    const instance = PreviewFileModal.openInstance;
    if (!instance) return;

    let containerHeight = $(window).height() * 0.66;
    let containerWidth = Math.min(
      (containerHeight / 3) * 4,
      $(window).width() - instance.settings.minGutter * 2
    );
    containerHeight = (containerWidth / 4) * 3;

    const $img = instance.#$container.find('img');
    $img.css({width: containerWidth, height: containerHeight});

    let imageRatio: number | undefined;

    if (instance.loaded && $img.length) {
      const maxWidth = $img.data('maxwidth');
      const maxHeight = $img.data('maxheight');
      imageRatio = maxWidth / maxHeight;
      const desiredWidth = instance.desiredWidth ?? instance.getWidth();
      const desiredHeight = instance.desiredHeight ?? instance.getHeight();
      let width = Math.min(desiredWidth, maxWidth);
      let height = Math.round(Math.min(maxHeight, width / imageRatio));
      if (height > desiredHeight) height = desiredHeight;
      width = Math.round(height * imageRatio);
      $img.css({width, height});
      instance._resizeContainer(width, height);
      instance.desiredWidth = width;
      instance.desiredHeight = height;
    }

    instance.updateSizeAndPosition();

    if (instance.loaded && $img.length && imageRatio !== undefined) {
      containerWidth = Math.round(
        Math.min(
          Math.max($img.height() * imageRatio),
          $(window).width() - instance.settings.minGutter * 2
        )
      );
      containerHeight = Math.round(
        Math.min(
          Math.max(containerWidth / imageRatio),
          $(window).height() - instance.settings.minGutter * 2
        )
      );
      containerWidth = Math.round(containerHeight * imageRatio);

      if (
        containerWidth >
        Math.min(
          containerWidth,
          $(window).width() - instance.settings.minGutter * 2
        )
      ) {
        containerWidth = Math.min(
          containerWidth,
          $(window).width() - instance.settings.minGutter * 2
        );
        containerHeight = containerWidth / imageRatio;
      }

      instance._resizeContainer(containerWidth, containerHeight);
      $img.css({width: containerWidth, height: containerHeight});

      // SAFETY: The image editor bundle owns the optional imageFocalPoint global.
      if ((window as any).imageFocalPoint) {
        // SAFETY: The guarded image editor global exposes renderFocal.
        (window as any).imageFocalPoint.renderFocal();
      }
    }
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

    const settings: any = {};
    if ($element.data('image-width')) {
      settings.startingWidth = $element.data('image-width');
      settings.startingHeight = $element.data('image-height');
    }
    new PreviewFileModal($element.data('id'), elementSelect, settings);
    return true;
  }
}
