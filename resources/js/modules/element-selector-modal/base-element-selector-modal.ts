import {
  Modal,
  ESC_KEY,
  isMobileBrowser,
  bod,
  type ModalSettings,
} from '@craftcms/garnish';
import {uiLayerManager} from '@/modules/slideout/slideout';

declare const Craft: any;
declare const $: any;

const noop = () => {};

type CriteriaValue =
  | string
  | number
  | boolean
  | null
  | CriteriaValue[]
  | ElementCriteria;

interface ElementCriteria {
  [key: string]: CriteriaValue;
}

interface LegacyElementInfo {
  id: string | number;
}

type LegacyIndexCallback = (...args: any[]) => any;

type ElementIndexSettingValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | string[]
  | number[]
  | ElementCriteria
  | BaseElementSelectorModal
  | LegacyIndexCallback;

export interface ElementIndexSettings {
  [key: string]: ElementIndexSettingValue;
}

export interface BaseElementSelectorModalSettings extends ModalSettings {
  fullscreen: boolean;
  storageKey: string | null;
  sources: string[] | null;
  condition: ElementCriteria | null;
  referenceElementId: number | null;
  referenceElementOwnerId: number | null;
  referenceElementSiteId: number | null;
  criteria: ElementCriteria | null;
  multiSelect: boolean;
  showSiteMenu: boolean | string | null;
  siteIds: number[] | null;
  disabledElementIds: number[];
  disableElementsOnSelect: boolean;
  hideOnSelect: boolean;
  modalTitle: string | null;
  showTitle: boolean;
  selectBtnLabel: string | null;
  onCancel(): void;
  onSelect(elementInfo: LegacyElementInfo, ...args: any[]): void;
  hideSidebar: boolean;
  defaultSiteId: number | null;
  defaultSource: string | null;
  defaultSourcePath: string | null;
  preferStoredSource: boolean;
  showSourcePath: boolean;
  bodyAction: string;
  indexSettings: ElementIndexSettings;
  transforms?: Array<{handle: string; name: string}>;
  disabledFolderIds?: number[];
}

// Non-runtime-dependent defaults, separated so the static getter can augment them.
const BASE_DEFAULTS = {
  // Modal settings preserved after we overwrite this.settings in init()
  resizable: true,
  hideOnShadeClick: true,
  // Modal lifecycle callbacks so super.onFadeIn/onShow/onHide don't throw
  onFadeIn: noop,
  onShow: noop,
  onHide: noop,
  // Craft-specific defaults
  fullscreen: false,
  storageKey: null,
  sources: null,
  condition: null,
  referenceElementId: null,
  referenceElementOwnerId: null,
  referenceElementSiteId: null,
  criteria: null,
  multiSelect: false,
  showSiteMenu: null,
  siteIds: null,
  disabledElementIds: [],
  disableElementsOnSelect: false,
  hideOnSelect: true,
  modalTitle: null,
  showTitle: false,
  selectBtnLabel: null,
  onCancel: noop,
  onSelect: noop,
  hideSidebar: false,
  defaultSiteId: null,
  defaultSource: null,
  defaultSourcePath: null,
  preferStoredSource: false,
  showSourcePath: true,
  bodyAction: 'element-selector-modals/body',
  indexSettings: {},
} satisfies Partial<BaseElementSelectorModalSettings>;

/**
 * BaseElementSelectorModal — a port of `Craft.BaseElementSelectorModal` onto the
 * modern `@craftcms/garnish` `Modal`. Loads an element index into a modal via a
 * server action, then lets the user select elements and fires `onSelect`.
 *
 * The modal shell is modern (`extends Modal`), but element-index interaction
 * retains the jQuery seam: the still-legacy `Craft.createElementIndex` produces
 * jQuery-based DOM, and the `doubletap` / activate events on that DOM are jQuery
 * synthetic events that must be bound via `$(el).on(...)`, NOT `addListener`
 * (which uses native `addEventListener` and would not receive jQuery-triggered
 * events).
 */
export class BaseElementSelectorModal extends Modal {
  static override defaults: BaseElementSelectorModalSettings = {
    ...Modal.defaults,
    ...BASE_DEFAULTS,
  };

  // Shadows Modal's typed `settings` so we can store our own key/value shape.
  declare settings: BaseElementSelectorModalSettings;

  // Not a static `defaults` override of Modal's typed defaults — we use a
  // module-level constant (BASE_DEFAULTS) and resolve Craft.t() values lazily
  // in init() where Craft is guaranteed available.

  elementType: string | null = null;
  elementIndex: any = null;
  supportSidebarToggleView = false;

  // jQuery refs to modal DOM — accessed by subclasses and external code.
  $body: any = null;
  $content: any = null;
  $footer: any = null;
  $selectBtn: any = null;
  $sidebar: any = null;
  $sources: any = null;
  $sourceToggles: any = null;
  $search: any = null;
  $elements: any = null;
  $tbody: any = null;
  $primaryButtons: any = null;
  $secondaryButtons: any = null;
  $cancelBtn: any = null;
  $main: any = null;

  $mainHeader: any = null;
  $sidebarHeader: any = null;
  $sidebarCloseBtn: any = null;
  $sidebarToggleBtn: any = null;
  $mainHeading: any = null;

  constructor(elementType: string, settings?: any) {
    // Construct Modal shell without a container and without auto-showing.
    // The actual container is built in init() and handed to setContainer().
    super(undefined, {autoShow: false});
    if (new.target === BaseElementSelectorModal) {
      this.init(elementType, settings);
    }
  }

  init(elementType: string, settings?: any): void {
    this.elementType = elementType;

    // Merge user settings over our defaults (resolves Craft.t() lazily here,
    // not at module load time where Craft may not exist yet).
    this.settings = Object.assign(
      {},
      BaseElementSelectorModal.defaults,
      this.settings,
      {
        modalTitle:
          BaseElementSelectorModal.defaults.modalTitle ??
          Craft.t('app', 'Select element'),
        selectBtnLabel:
          BaseElementSelectorModal.defaults.selectBtnLabel ??
          Craft.t('app', 'Select'),
      },
      settings
    );

    const headingId =
      'elementSelectorModalHeading-' + Math.floor(Math.random() * 1000000);

    const $container = $('<div/>', {
      class: 'modal elementselectormodal',
      'aria-labelledby': headingId,
    }).appendTo($(bod));

    const $headingContainer = $('<div/>', {
      class: this.settings.showTitle ? 'header' : 'visually-hidden',
    }).appendTo($container);
    $('<h1/>', {id: headingId, text: this.settings.modalTitle}).appendTo(
      $headingContainer
    );

    this.$body = $('<div/>', {class: 'body'})
      .append($('<div/>', {class: 'spinner big'}))
      .appendTo($container);

    this.$footer = $('<div/>', {class: 'footer'}).appendTo($container);

    if (this.settings.fullscreen) {
      $container.addClass('fullscreen');
      this.settings.minGutter = 0;
    }

    this.setContainer($container[0]);

    this.$secondaryButtons = $(
      '<div class="buttons left secondary-buttons"/>'
    ).appendTo(this.$footer);
    this.$primaryButtons = $('<div class="buttons right"/>').appendTo(
      this.$footer
    );

    this.$cancelBtn = $('<button/>', {
      type: 'button',
      class: 'btn',
      text: Craft.t('app', 'Cancel'),
    }).appendTo(this.$primaryButtons);

    this.$selectBtn = Craft.ui
      .createSubmitButton({
        class: 'disabled',
        label: this.settings.selectBtnLabel,
        spinner: true,
      })
      .attr('aria-disabled', 'true')
      .appendTo(this.$primaryButtons);

    // Use native click — <button> fires click on keyboard activation natively.
    this.addListener(this.$cancelBtn[0], 'click', () => this.cancel());
    this.addListener(this.$selectBtn[0], 'click', () => this.selectElements());

    this.show();
  }

  updateModalBottomPadding(): void {
    const footerHeight = this.$footer.outerHeight();
    const bottomPadding = parseInt($(this.$container).css('padding-bottom'));
    if (footerHeight !== bottomPadding) {
      $(this.$container).css('padding-bottom', footerHeight);
    }
  }

  updateSidebarView(): void {
    if (!this.supportSidebarToggleView) return;

    if (this.sidebarShouldBeHidden()) {
      if (!this.$sidebarToggleBtn) this.buildSidebarToggleView();
    } else {
      if (this.$sidebarToggleBtn) this.resetView();
    }
  }

  sidebarShouldBeHidden(): boolean {
    return $(this.$container).outerWidth() < 550;
  }

  resetView(): void {
    this.$mainHeader?.remove();
    this.$sidebarHeader?.remove();
    this.$sidebarToggleBtn = null;
    this.$body.addClass('has-sidebar');
    this.$content.addClass('has-sidebar');
    this.$sidebar.removeClass('hidden');
  }

  buildSidebarToggleView(): void {
    if (this.$sidebarToggleBtn || !this.sidebarShouldBeHidden()) return;

    this.$sidebarHeader = $('<div class="sidebar-header"/>').prependTo(
      this.$sidebar
    );

    this.$sidebarCloseBtn = Craft.ui
      .createButton({class: 'nav-close close-btn'})
      .attr('aria-label', Craft.t('app', 'Close'))
      .removeClass('btn')
      .appendTo(this.$sidebarHeader);

    this.$mainHeader = $('<div class="main-header"/>').prependTo(this.$main);
    this.$mainHeading = $(
      `<h2 class="main-heading">${this.getActiveSourceName()}</h2>`
    ).appendTo(this.$mainHeader);

    this.$sidebarToggleBtn = Craft.ui
      .createButton({
        toggle: true,
        controls: 'modal-sidebar',
        class: 'nav-toggle',
      })
      .removeClass('btn')
      .attr('aria-label', Craft.t('app', 'Show sidebar'))
      .appendTo(this.$mainHeader);

    this.$sidebar.attr('id', 'modal-sidebar');
    this.closeSidebar();

    this.addListener(this.$sidebarToggleBtn[0], 'click', () =>
      this.toggleSidebar()
    );
    this.addListener(this.$sidebarCloseBtn[0], 'click', () =>
      this.toggleSidebar()
    );
  }

  sidebarIsOpen(): boolean {
    return this.$sidebarToggleBtn?.attr('aria-expanded') === 'true';
  }

  toggleSidebar(): void {
    if (this.sidebarIsOpen()) {
      this.closeSidebar();
    } else {
      this.openSidebar();
    }
  }

  openSidebar(): void {
    this.$body.addClass('has-sidebar');
    this.$content.addClass('has-sidebar');
    this.$sidebar.removeClass('hidden');
    this.$sidebarToggleBtn.attr('aria-expanded', 'true');
    this.$sidebar.find(':focusable').first().focus();

    uiLayerManager().addLayer(this.$sidebar[0]);
    uiLayerManager().registerShortcut(ESC_KEY, () => {
      this.closeSidebar();
    });
  }

  closeSidebar(): void {
    if (!this.$sidebarToggleBtn) return;

    if (this.sidebarIsOpen()) {
      uiLayerManager().removeLayer();
    }

    this.$sidebar.addClass('hidden');
    this.$sidebarToggleBtn.attr('aria-expanded', 'false');

    const focusedEl = document.activeElement;
    if (focusedEl && this.$sidebar[0].contains(focusedEl)) {
      this.$sidebarToggleBtn.focus();
    }

    this.$body.removeClass('has-sidebar');
    this.$content.removeClass('has-sidebar');
  }

  getActiveSourceName(): string {
    return this.$sidebar.find('.sel').text();
  }

  override onFadeIn(): void {
    if (!this.elementIndex) {
      this._createElementIndex();
    } else {
      this.updateModalBottomPadding();
      if (!isMobileBrowser()) {
        this.elementIndex.$search.focus();
      }
    }
    super.onFadeIn();
  }

  onSelectionChange(): void {
    this.updateSelectBtnState();
  }

  onSelectSource(): void {
    this.updateHeading();
    this.updateModalBottomPadding();
  }

  updateHeading(): void {
    if (!this.$mainHeading) return;
    this.$mainHeading.text(this.getActiveSourceName());
  }

  updateSelectBtnState(): void {
    if (this.$selectBtn) {
      if (this.shouldEnableSelectBtn()) {
        this.enableSelectBtn();
      } else {
        this.disableSelectBtn();
      }
    }
  }

  shouldEnableSelectBtn(): boolean {
    return this.hasSelection();
  }

  hasSelection(): boolean {
    return !!this.elementIndex?.getSelectedElements().length;
  }

  enableSelectBtn(): void {
    this.$selectBtn.removeClass('disabled').attr('aria-disabled', 'false');
  }

  disableSelectBtn(): void {
    this.$selectBtn.addClass('disabled').attr('aria-disabled', 'true');
  }

  enableCancelBtn(): void {
    this.$cancelBtn.removeClass('disabled');
  }

  disableCancelBtn(): void {
    this.$cancelBtn.addClass('disabled');
  }

  showFooterSpinner(): void {
    this.$selectBtn.addClass('loading');
  }

  hideFooterSpinner(): void {
    this.$selectBtn.removeClass('loading');
  }

  cancel(): void {
    if (!this.$cancelBtn.hasClass('disabled')) {
      this.hide();
    }
  }

  selectElements(): void {
    if (this.hasSelection()) {
      if (this.elementIndex.view?.elementSelect) {
        this.elementIndex.view.elementSelect.clearMouseUpTimeout();
      }

      const $selectedElements = this.elementIndex.getSelectedElements();
      const elementInfo = this.getElementInfo($selectedElements);

      this.onSelect(elementInfo);

      if (this.settings.disableElementsOnSelect) {
        this.elementIndex.disableElements(
          this.elementIndex.getSelectedElements()
        );
      }

      if (this.settings.hideOnSelect) {
        this.hide();
      }
    }
  }

  getElementInfo($selectedElements: any): any[] {
    const info = [];
    for (let i = 0; i < $selectedElements.length; i++) {
      const $element = $($selectedElements[i]);
      info.push(Craft.getElementInfo($element));
    }
    return info;
  }

  override onShow(): void {
    this.updateSelectBtnState();

    // Resize listeners are added fresh each show since Modal.hide() removes
    // all listeners registered via addListener in legacy Garnish. In modern
    // Garnish they are not removed on hide, but re-adding is idempotent
    // provided we track and clear them properly — for simplicity we keep the
    // legacy pattern of adding per-show.
    this.addListener(window, 'resize', () => this.updateSidebarView());
    this.addListener(window, 'resize', () => this.updateModalBottomPadding());

    this.updateModalBottomPadding();
    this.updateSidebarView();

    if (this.elementIndex?.searching) {
      this.elementIndex.clearSearch(true);
    }

    super.onShow();
  }

  override onHide(): void {
    this.closeSidebar();
    super.onHide();
  }

  onSelect(elementInfo: any): void {
    this.settings.onSelect(elementInfo);
  }

  override disable(): void {
    this.elementIndex?.disable();
    super.disable();
  }

  override enable(): void {
    this.elementIndex?.enable();
    super.enable();
  }

  getElementIndexParams() {
    return {
      context: 'modal',
      elementType: this.elementType,
      sources: this.settings.sources,
      condition: this.settings.condition,
      showSiteMenu:
        this.settings.showSiteMenu !== null &&
        this.settings.showSiteMenu !== 'auto'
          ? this.settings.showSiteMenu
            ? '1'
            : '0'
          : undefined,
      siteIds: this.settings.siteIds || undefined,
    };
  }

  _createElementIndex(): void {
    Craft.sendActionRequest('POST', this.settings.bodyAction, {
      data: this.getElementIndexParams(),
    }).then((response: any) => {
      this.$body.html(response.data.html);

      if (this.$body.has('.sidebar:not(.hidden)').length) {
        this.$body.addClass('has-sidebar');
        this.supportSidebarToggleView = true;
      }

      this.elementIndex = Craft.createElementIndex(
        this.elementType,
        this.$body.children('.element-index'),
        this.getIndexSettings()
      );

      this.$main = this.elementIndex.$main;
      this.$sidebar = this.elementIndex.$sidebar;
      this.$content = this.$body.find('.content');

      this.updateSidebarView();
      this.updateModalBottomPadding();

      // doubletap is a jQuery synthetic event — must use jQuery .on(), NOT
      // addListener (which uses native addEventListener and won't receive
      // jQuery-triggered events).
      $(this.elementIndex.$elements).on(
        'doubletap',
        (ev: any, touchData: any) => {
          if (touchData.firstTap.target === touchData.secondTap.target) {
            this.selectElements();
          }
        }
      );

      this.on('updateSizeAndPosition', () => {
        this.elementIndex.handleResize();
      });

      this.updateSelectBtnState();
    });
  }

  getIndexSettings(): ElementIndexSettings {
    return Object.assign(
      {
        context: 'modal',
        modal: this,
        storageKey: this.settings.storageKey,
        condition: this.settings.condition,
        referenceElementId: this.settings.referenceElementId,
        referenceElementOwnerId: this.settings.referenceElementOwnerId,
        referenceElementSiteId: this.settings.referenceElementSiteId,
        criteria: Object.assign({}, this.settings.criteria),
        disabledElementIds: this.settings.disabledElementIds,
        selectable: true,
        multiSelect: this.settings.multiSelect,
        waitForDoubleClicks: true,
        buttonContainer: this.$secondaryButtons,
        onSelectionChange: () => {
          if (this.elementIndex) {
            this.onSelectionChange();
          }
        },
        onSourcePathChange: () => {
          if (this.elementIndex) {
            this.onSelectionChange();
          }
        },
        onSelectSource: this.onSelectSource.bind(this),
        hideSidebar: this.settings.hideSidebar,
        defaultSiteId: this.settings.defaultSiteId,
        defaultSource: this.settings.defaultSource,
        defaultSourcePath: this.settings.defaultSourcePath,
        preferStoredSource: this.settings.preferStoredSource,
        showSourcePath: this.settings.showSourcePath,
      },
      this.settings.indexSettings
    );
  }
}
