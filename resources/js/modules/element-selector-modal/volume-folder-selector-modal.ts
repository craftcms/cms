import {ESC_KEY, hasAttr} from '@craftcms/garnish';
import {
  VolumeFolderSelectorController,
  type SourcePathSegment,
  type VolumeFolderSelectorOptions,
} from '@craftcms/ui';
import type CraftElementSelectorModal from '@craftcms/ui/components/element-selector-modal/element-selector-modal';
import '@craftcms/ui/components/element-selector-modal/element-selector-modal';
import {uiLayerManager} from '@/modules/slideout/slideout';

declare const Craft: any;
declare const $: any;

/** Below this the index's sidebar collapses behind a toggle. */
const NARROW_THRESHOLD = 550;

export interface VolumeFolderSelectorModalSettings extends VolumeFolderSelectorOptions {
  /** Legacy Garnish setting, accepted and ignored. */
  closeOtherModals?: boolean;
}

/**
 * The folder picker used by asset moves.
 *
 * The one selector still driving the **legacy jQuery element index** rather than
 * the Vue one, because folder picking keys off that index's `sourcePath` — the
 * breadcrumb of the folder you have navigated into, which is what "select the
 * folder I'm looking at" means when no row is highlighted. It is the last thing
 * keeping `ElementIndexHtml` and the HTML `element-indexes/*` endpoints alive.
 *
 * It is also the proof that the chrome works with no Vue at all: the same
 * `<craft-element-selector-modal>` and the same controller the Vue modal uses,
 * with server HTML slotted in instead of a component tree.
 *
 * Opened `non-modal` deliberately. `showModal()` puts a dialog in the top layer,
 * where it paints above every menu the legacy CP appends to `<body>` — the
 * breadcrumb, status and site menus this index depends on — and makes them
 * unclickable.
 *
 * @example
 * new Craft.VolumeFolderSelectorModal({
 *   sources: ['volume:…'],
 *   disabledFolderIds: [12],
 *   onSelect: ([folder]) => move(folder.folderId),
 * });
 */
export class VolumeFolderSelectorModal {
  readonly controller: VolumeFolderSelectorController;
  readonly element: CraftElementSelectorModal;

  /** The legacy jQuery index, once booted. */
  elementIndex: any = null;

  #booted = false;
  #resizeObserver: ResizeObserver | null = null;
  #offChange: (() => void) | null = null;

  // Narrow-viewport sidebar chrome, built lazily.
  #$sidebar: any = null;
  #$main: any = null;
  #$content: any = null;
  #$sidebarHeader: any = null;
  #$mainHeader: any = null;
  #$sidebarToggleBtn: any = null;
  #$mainHeading: any = null;

  constructor(settings: VolumeFolderSelectorModalSettings = {}) {
    const {closeOtherModals: _ignored, ...options} = settings;

    this.controller = new VolumeFolderSelectorController(options);

    this.element = document.createElement(
      'craft-element-selector-modal'
    ) as CraftElementSelectorModal;
    this.element.controller = this.controller;
    this.element.nonModal = true;
    this.element.classList.add('elementselectormodal');
    document.body.append(this.element);

    this.#offChange = this.controller.on('change', (state) => {
      if (state.indexBody) {
        this.#bootIndex(state.indexBody.html);
      }
    });

    this.controller.on('close', () => this.#closeSidebar());

    void this.controller.open();
  }

  show(): Promise<void> {
    return this.controller.open();
  }

  hide(): void {
    this.controller.close();
  }

  destroy(): void {
    this.#offChange?.();
    this.#offChange = null;
    this.#resizeObserver?.disconnect();
    this.#resizeObserver = null;
    this.controller.destroy();
    this.element.remove();
  }

  /**
   * Boots the legacy index into the modal's default slot, once.
   *
   * The HTML goes into light DOM rather than the component's shadow root, so the
   * CP stylesheet — which this markup depends on entirely — still reaches it.
   */
  #bootIndex(html: string): void {
    if (this.#booted) {
      return;
    }

    this.#booted = true;
    this.element.innerHTML = html;

    const $body = $(this.element);
    this.elementIndex = Craft.createElementIndex(
      this.controller.elementType,
      $body.children('.element-index'),
      this.#indexSettings()
    );

    this.#$main = this.elementIndex.$main;
    this.#$sidebar = this.elementIndex.$sidebar;
    this.#$content = $body.find('.content');

    // `sourcePath` is read live, not captured: the controller consults it on
    // every `canSubmit` evaluation, and it changes as the user navigates. The
    // getter delegates to an arrow so it closes over this modal rather than the
    // object literal it sits on.
    const sourcePath = (): readonly SourcePathSegment[] =>
      (this.elementIndex?.sourcePath ?? []) as SourcePathSegment[];

    this.controller.attachIndex({
      clearSelection: () => this.elementIndex?.clearSelection?.(),
      get sourcePath(): readonly SourcePathSegment[] {
        return sourcePath();
      },
      destroy: () => this.elementIndex?.destroy?.(),
    });

    // Double-click chooses. Kept on jQuery: `doubletap` is a jQuery-synthetic
    // event, so `addEventListener` would never see it.
    $(this.elementIndex.$elements).on(
      'doubletap',
      (_ev: any, touchData: any) => {
        if (touchData.firstTap.target === touchData.secondTap.target) {
          void this.controller.submit();
        }
      }
    );

    if (typeof ResizeObserver !== 'undefined') {
      this.#resizeObserver = new ResizeObserver(() => {
        this.elementIndex?.handleResize?.();
        this.#updateSidebarView();
      });
      this.#resizeObserver.observe(this.element);
    }

    this.#updateSidebarView();
  }

  /**
   * The index's own configuration.
   *
   * The controller supplies the query half; the callbacks are the binder's,
   * because they are what pushes the legacy index's state into the controller.
   */
  #indexSettings(): Record<string, unknown> {
    const pushSelection = () => {
      const selected = this.elementIndex?.getSelectedElements?.() ?? [];
      const elements = [];

      for (let i = 0; i < selected.length; i++) {
        // `.first()`, not the deprecated `:first` positional selector — jQuery's
        // engine does not resolve that one here and silently matches nothing,
        // which turned every folder id into NaN.
        const $element = selected.eq(i).find('.element').first();
        const folderId = parseInt($element.data('folder-id'), 10);

        elements.push({
          id: folderId,
          folderId,
          siteId: null,
          label: String($element.data('label') ?? ''),
          status: null,
          url: null,
          hasThumb: false,
        });
      }

      this.controller.setSelection(elements as any);
    };

    return {
      ...this.controller.indexSettings(),
      buttonContainer: this.element.querySelector('[slot="secondary-actions"]'),
      onSelectionChange: pushSelection,
      // The current folder counts as a selection candidate, so a breadcrumb
      // change has to re-run the controller's rules too.
      onSourcePathChange: pushSelection,
      onSelectSource: () => this.#updateHeading(),
      viewSettings: () => ({
        canSelectElement: ($element: any) =>
          hasAttr($element.find('.element').first()[0], 'data-folder-id'),
      }),
    };
  }

  // ───────────────────── narrow-viewport sidebar ─────────────────────

  get #supportsSidebarToggle(): boolean {
    return !!this.#$sidebar?.length && !this.#$sidebar.hasClass('hidden');
  }

  get #sidebarShouldBeHidden(): boolean {
    return this.element.getBoundingClientRect().width < NARROW_THRESHOLD;
  }

  #updateSidebarView(): void {
    if (!this.#supportsSidebarToggle) {
      return;
    }

    if (this.#sidebarShouldBeHidden) {
      this.#buildSidebarToggleView();
    } else if (this.#$sidebarToggleBtn) {
      this.#resetSidebarView();
    }
  }

  #buildSidebarToggleView(): void {
    if (this.#$sidebarToggleBtn) {
      return;
    }

    // `btn-empty` without `btn` reproduces what the legacy chrome emitted: an
    // icon-only button taking its appearance from `nav-close` / `nav-toggle`.
    const sidebarHeader = document.createElement('div');
    sidebarHeader.className = 'sidebar-header';
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'nav-close close-btn btn-empty';
    closeBtn.setAttribute('aria-label', Craft.t('app', 'Close'));
    sidebarHeader.append(closeBtn);
    this.#$sidebar[0].prepend(sidebarHeader);
    this.#$sidebarHeader = $(sidebarHeader);

    const mainHeader = document.createElement('div');
    mainHeader.className = 'main-header';
    const heading = document.createElement('h2');
    heading.className = 'main-heading';
    heading.textContent = this.#activeSourceName();
    const toggleBtn = document.createElement('button');
    toggleBtn.type = 'button';
    toggleBtn.className = 'nav-toggle btn-empty';
    toggleBtn.setAttribute('aria-expanded', 'false');
    toggleBtn.setAttribute('aria-controls', 'modal-sidebar');
    toggleBtn.setAttribute('aria-label', Craft.t('app', 'Show sidebar'));
    mainHeader.append(heading, toggleBtn);
    this.#$main[0].prepend(mainHeader);
    this.#$mainHeader = $(mainHeader);
    this.#$mainHeading = $(heading);
    this.#$sidebarToggleBtn = $(toggleBtn);

    this.#$sidebar.attr('id', 'modal-sidebar');
    this.#closeSidebar();

    toggleBtn.addEventListener('click', () => this.#toggleSidebar());
    closeBtn.addEventListener('click', () => this.#toggleSidebar());
  }

  #resetSidebarView(): void {
    this.#$mainHeader?.remove();
    this.#$sidebarHeader?.remove();
    this.#$sidebarToggleBtn = null;
    this.#$sidebar.removeClass('hidden');
    this.#$content?.addClass('has-sidebar');
  }

  #sidebarIsOpen(): boolean {
    return this.#$sidebarToggleBtn?.attr('aria-expanded') === 'true';
  }

  #toggleSidebar(): void {
    if (this.#sidebarIsOpen()) {
      this.#closeSidebar();
    } else {
      this.#openSidebar();
    }
  }

  #openSidebar(): void {
    this.#$content?.addClass('has-sidebar');
    this.#$sidebar.removeClass('hidden');
    this.#$sidebarToggleBtn.attr('aria-expanded', 'true');
    this.#$sidebar.find(':focusable').first().focus();

    uiLayerManager().addLayer(this.#$sidebar[0]);
    uiLayerManager().registerShortcut(ESC_KEY, () => this.#closeSidebar());
  }

  #closeSidebar(): void {
    if (!this.#$sidebarToggleBtn) {
      return;
    }

    if (this.#sidebarIsOpen()) {
      uiLayerManager().removeLayer();
    }

    this.#$sidebar.addClass('hidden');
    this.#$sidebarToggleBtn.attr('aria-expanded', 'false');

    const focused = document.activeElement;
    if (focused && this.#$sidebar[0].contains(focused)) {
      this.#$sidebarToggleBtn.focus();
    }

    this.#$content?.removeClass('has-sidebar');
  }

  #activeSourceName(): string {
    return this.#$sidebar?.find('.sel').text() ?? '';
  }

  #updateHeading(): void {
    this.#$mainHeading?.text(this.#activeSourceName());
  }
}
