import {
  Modal,
  ESC_KEY,
  isMobileBrowser,
  bod,
  type ModalSettings,
} from '@craftcms/garnish';
import {html, render, type TemplateResult} from 'lit';
import type {App} from 'vue';
import type {SelectedElement} from './useModalElementIndex';
import {ref} from 'lit/directives/ref.js';
import {uiLayerManager} from '@/modules/slideout/slideout';

declare const Craft: any;
declare const $: any;

const noop = () => {};

/** What `ModalElementIndex` exposes back to the modal shell. */
type ModalElementIndexInstance = {
  clearSelection(): void;
};

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
  storageKey: null as string | null,
  sources: null as string[] | null,
  condition: null as any,
  referenceElementId: null as number | null,
  referenceElementOwnerId: null as number | null,
  referenceElementSiteId: null as number | null,
  criteria: null as Record<string, any> | null,
  multiSelect: false,
  showSiteMenu: null as boolean | string | null,
  siteIds: null as number[] | null,
  disabledElementIds: [] as number[],
  disableElementsOnSelect: false,
  hideOnSelect: true,
  modalTitle: null as string | null,
  showTitle: false,
  selectBtnLabel: null as string | null,
  onCancel: noop,
  onSelect: noop as (elementInfo: any, ...args: any[]) => void,
  hideSidebar: false,
  defaultSiteId: null as number | null,
  defaultSource: null as string | null,
  defaultSourcePath: null as string | null,
  preferStoredSource: false,
  showSourcePath: true,
  bodyAction: 'element-selector-modals/body',
  indexSettings: {} as Record<string, any>,
};

/**
 * BaseElementSelectorModal — a port of `Craft.BaseElementSelectorModal` onto the
 * modern `@craftcms/garnish` `Modal`. Loads an element index into a modal via a
 * server action, then lets the user select elements and fires `onSelect`.
 *
 * Its own chrome is built with Lit templates (`chromeTemplate()`,
 * `buildSidebarToggleView()`); the `$`-prefixed fields are jQuery wrappers around
 * those nodes, since subclasses and the element select input manipulate them.
 *
 * The modal shell is modern (`extends Modal`), but element-index interaction
 * retains the jQuery seam: the still-legacy `Craft.createElementIndex` produces
 * jQuery-based DOM, and the `doubletap` / activate events on that DOM are jQuery
 * synthetic events that must be bound via `$(el).on(...)`, NOT `addListener`
 * (which uses native `addEventListener` and would not receive jQuery-triggered
 * events).
 */
export class BaseElementSelectorModal extends Modal {
  static override defaults: ModalSettings & typeof BASE_DEFAULTS =
    Object.assign({}, Modal.defaults, BASE_DEFAULTS);

  // Shadows Modal's typed `settings` so we can store our own key/value shape.
  declare settings: any;

  // Not a static `defaults` override of Modal's typed defaults — we use a
  // module-level constant (BASE_DEFAULTS) and resolve Craft.t() values lazily
  // in init() where Craft is guaranteed available.

  elementType: string | null = null;
  /** The mounted Vue index, and the app hosting it. */
  index: ModalElementIndexInstance | null = null;
  indexApp: App | null = null;
  /** Pushed up by the index whenever its selection changes. */
  selectedElements: SelectedElement[] = [];
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

    // The container is created by hand rather than by the template: `render()`
    // owns its container's children, and `setContainer()` needs an element that
    // outlives the render.
    const container = document.createElement('div');
    container.className = 'modal elementselectormodal';
    container.setAttribute('aria-labelledby', headingId);

    if (this.settings.fullscreen) {
      container.classList.add('fullscreen');
      this.settings.minGutter = 0;
    }

    render(this.chromeTemplate(headingId), container);
    bod.append(container);
    this.setContainer(container);

    // Use native click — <button> fires click on keyboard activation natively.
    this.addListener(this.$cancelBtn[0], 'click', () => this.cancel());
    this.addListener(this.$selectBtn[0], 'click', () => this.selectElements());

    this.show();
  }

  /**
   * The modal's own chrome — heading, body, footer and the two footer buttons.
   *
   * The `$`-prefixed fields stay jQuery objects: subclasses and the element
   * select input reach for them (`$primaryButtons.append(…)`,
   * `$selectBtn.addClass('loading')`), so only their construction moves here.
   *
   * The select button reproduces what `Craft.ui.createSubmitButton()` emits —
   * `btn submit`, a `.label` inside an `.inline-flex` wrapper, and an absolutely
   * positioned spinner — because the CP stylesheet selects on all three.
   */
  protected chromeTemplate(headingId: string): TemplateResult {
    return html`
      <div class=${this.settings.showTitle ? 'header' : 'visually-hidden'}>
        <h1 id=${headingId}>${this.settings.modalTitle}</h1>
      </div>
      <div class="body" ${ref((el) => (this.$body = $(el)))}>
        <div class="spinner big"></div>
      </div>
      <div class="footer" ${ref((el) => (this.$footer = $(el)))}>
        <div
          class="buttons left secondary-buttons"
          ${ref((el) => (this.$secondaryButtons = $(el)))}
        ></div>
        <div
          class="buttons right"
          ${ref((el) => (this.$primaryButtons = $(el)))}
        >
          <button
            type="button"
            class="btn"
            ${ref((el) => (this.$cancelBtn = $(el)))}
          >
            ${Craft.t('app', 'Cancel')}
          </button>
          <button
            type="submit"
            class="btn disabled submit"
            aria-disabled="true"
            ${ref((el) => (this.$selectBtn = $(el)))}
          >
            <div class="inline-flex gap-xs">
              <div class="label">${this.settings.selectBtnLabel}</div>
            </div>
            <div class="spinner spinner-absolute"></div>
          </button>
        </div>
      </div>
    `;
  }

  /**
   * Renders a template and hands back its root element, detached.
   *
   * For markup that has to be inserted into DOM this class doesn't own — the
   * element index's sidebar and main column, which the legacy index renders.
   * `render()` would claim those containers and wipe them, so the template is
   * rendered into a throwaway host and its root moved out. Safe only because
   * none of these are ever re-rendered.
   */
  protected renderElement(template: TemplateResult): HTMLElement {
    const host = document.createElement('div');
    render(template, host);

    return host.firstElementChild as HTMLElement;
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

    // `btn-empty` and the missing `btn` reproduce what `Craft.ui.createButton()`
    // plus the `.removeClass('btn')` these two always did emit: an icon-only
    // button that takes its appearance from `nav-close` / `nav-toggle`.
    const sidebarHeader = this.renderElement(html`
      <div class="sidebar-header">
        <button
          type="button"
          class="nav-close close-btn btn-empty"
          aria-label=${Craft.t('app', 'Close')}
          ${ref((el) => (this.$sidebarCloseBtn = $(el)))}
        ></button>
      </div>
    `);
    this.$sidebar[0].prepend(sidebarHeader);
    this.$sidebarHeader = $(sidebarHeader);

    const mainHeader = this.renderElement(html`
      <div class="main-header">
        <h2 class="main-heading" ${ref((el) => (this.$mainHeading = $(el)))}>
          ${this.getActiveSourceName()}
        </h2>
        <button
          type="button"
          class="nav-toggle btn-empty"
          aria-expanded="false"
          aria-controls="modal-sidebar"
          aria-label=${Craft.t('app', 'Show sidebar')}
          ${ref((el) => (this.$sidebarToggleBtn = $(el)))}
        ></button>
      </div>
    `);
    this.$main[0].prepend(mainHeader);
    this.$mainHeader = $(mainHeader);

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
    if (!this.index) {
      this._createElementIndex();
    } else {
      this.updateModalBottomPadding();
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
    return this.selectedElements.length > 0;
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
    if (!this.hasSelection()) {
      return;
    }

    const elementInfo = this.getElementInfo(this.selectedElements);

    this.onSelect(elementInfo);

    if (this.settings.disableElementsOnSelect) {
      // Selected elements become unselectable, so the same one can't be picked
      // twice while the modal stays open.
      this.settings.disabledElementIds = [
        ...(this.settings.disabledElementIds ?? []),
        ...this.selectedElements.map((element) => element.id),
      ];
      this.index?.clearSelection();
    }

    if (this.settings.hideOnSelect) {
      this.hide();
    }
  }

  /**
   * Hook for subclasses to adjust what gets handed to `onSelect` — the asset
   * modal splices a transformed URL in here.
   *
   * These arrive as plain objects from the index rather than being read back
   * off chip data attributes, but carry the same keys `Craft.getElementInfo()`
   * produced, since the relation field consumes them either way.
   */
  getElementInfo(elements: SelectedElement[]): any[] {
    return elements.map((element) => ({...element}));
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

    super.onShow();
  }

  override onHide(): void {
    this.closeSidebar();
    super.onHide();
  }

  /**
   * The index is a Vue app rather than markup the modal owns, so it has to be
   * unmounted explicitly — `Modal.destroy()` only clears the container.
   */
  override destroy(): void {
    this.indexApp?.unmount();
    this.indexApp = null;
    this.index = null;
    this.selectedElements = [];

    super.destroy();
  }

  onSelect(elementInfo: any): void {
    this.settings.onSelect(elementInfo);
  }

  override disable(): void {
    super.disable();
  }

  override enable(): void {
    super.enable();
  }

  getElementIndexParams(): Record<string, any> {
    const params: Record<string, any> = {
      context: 'modal',
      elementType: this.elementType,
      sources: this.settings.sources,
      condition: this.settings.condition,
    };

    if (
      this.settings.showSiteMenu !== null &&
      this.settings.showSiteMenu !== 'auto'
    ) {
      params.showSiteMenu = this.settings.showSiteMenu ? '1' : '0';
    }

    if (this.settings.siteIds) {
      params.siteIds = this.settings.siteIds;
    }

    return params;
  }

  _createElementIndex(): void {
    Craft.sendActionRequest('POST', this.settings.bodyAction, {
      data: this.getElementIndexParams(),
    }).then(async (response: any) => {
      // Loaded on open, not at module scope: this pulls Vue and the whole
      // element index component tree, which every page carrying a relation
      // field would otherwise pay for whether or not a modal is ever opened.
      const [
        {createApp},
        {default: ModalElementIndex},
        {createCpComponentRegistry},
      ] = await Promise.all([
        import('vue'),
        import('./ModalElementIndex.vue'),
        import('@/bootstrap/components'),
      ]);

      this.$body.empty();
      this.$content = this.$body;

      const host = document.createElement('div');
      host.className = 'modal-index-host';
      this.$body[0].append(host);

      this.indexApp = createApp(ModalElementIndex, {
        url: Craft.getActionUrl(this.settings.bodyAction),
        initial: response.data.props,
        params: this.getElementIndexParams(),
        disabledElementIds: this.settings.disabledElementIds ?? [],
        onSelectionChange: (elements: SelectedElement[]) => {
          this.selectedElements = elements;
          this.onSelectionChange();
        },
        onChoose: () => this.selectElements(),
      });

      createCpComponentRegistry().install(this.indexApp);
      this.indexApp.config.compilerOptions.isCustomElement = (tag: string) =>
        tag.includes('-');
      this.index = this.indexApp.mount(
        host
      ) as unknown as ModalElementIndexInstance;

      this.updateModalBottomPadding();
      this.updateSelectBtnState();
    });
  }

  getIndexSettings(): Record<string, any> {
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
        onSelectionChange: () => this.onSelectionChange(),
        onSourcePathChange: () => this.onSelectionChange(),
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
