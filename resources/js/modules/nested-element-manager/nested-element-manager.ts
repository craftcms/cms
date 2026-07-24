import {
  Base,
  DragSort,
  Select,
  firstFocusableElement,
  hasAttr,
  isCtrlKeyPressed,
  type GarnishBaseSettings,
} from '@craftcms/garnish';
import {nestedElementManagerData} from './support';

// `Craft`, `Garnish` (legacy), and `$` (jQuery) remain page globals. This
// class is an orchestrator of still-jQuery Craft widgets, so jQuery survives
// at those seams: `Craft.ui.createButton`/`createPasteButton` (jQuery
// factories), the `disclosureMenu()` / `expandableButton()` jQuery plugins,
// `Craft.createElementIndex` (jQuery-driven element index),
// `Craft.createElementEditor` (slideout), the `Craft.cp` singleton
// (announce/notices/copy-paste registry/thumb loader), the legacy
// `Craft.ElementEditor` found via `.data('elementEditor')`, and
// `Craft.sendActionRequest`. See the module README for the seam inventory.
declare const Craft: any;
declare const $: any;

/**
 * The per-element-type name tuple registered in `Craft.elementTypeNames`
 * (display name, handle, lower display name, plural lower display name).
 */
const typeName = (elementType: string, index: number): string =>
  Craft.elementTypeNames[elementType]?.[index] ?? Craft.t('app', 'element');

/**
 * A `createAttributes` entry: one "New X" menu option.
 */
export interface CreateAttributes {
  label: string;
  /** SVG markup for the option icon. */
  icon?: string | null;
  color?: string | null;
  group?: string | null;
  /** The element attributes posted to `elements/create`. */
  attributes?: Record<string, unknown>;
}

/**
 * Settings for {@link NestedElementManager}, matching the legacy
 * `Craft.NestedElementManager.defaults` (plus `elementType`, which the legacy
 * class took as a separate constructor argument).
 */
export interface NestedElementManagerSettings extends GarnishBaseSettings {
  /** The nested element type's class name. */
  elementType: string;
  /** `'cards'` for the card grid, anything else boots an embedded element index. */
  mode: 'cards' | 'index';
  /** Cards only: render as a wrapping grid instead of a vertical list. */
  showInGrid: boolean;
  ownerElementType: string | null;
  ownerId: number | null;
  ownerSiteId: number | null;
  /** The owner attribute (or `field:<handle>`) the nested elements belong to. */
  attribute: string | null;
  selectable: boolean;
  sortable: boolean;
  /** Extra settings for the embedded element index (index mode). */
  indexSettings: Record<string, unknown>;
  canCreate: boolean;
  /**
   * Whether copied elements may be pasted here. May be a boolean, a predicate,
   * or (legacy PHP settings) the *source string* of a predicate.
   */
  canPaste: boolean | string | ((elementInfo: any[]) => boolean);
  minElements: number | null;
  maxElements: number | null;
  createButtonLabel: string;
  /** Request param name the owner ID is sent under. */
  ownerIdParam: string | null;
  /** Attributes for created elements; an array renders a disclosure menu of options. */
  createAttributes: CreateAttributes[] | Record<string, unknown> | null;
  /** Extra params merged into paste requests. */
  pasteAttributes: Record<string, unknown> | null;
  fieldId: number | null;
  fieldHandle: string | null;
  /** Base input name used to mark the owner form dirty (delta tracking). */
  baseInputName: string | null;
  deleteLabel: string | null;
  deleteConfirmationMessage: string | null;
  bulkDeleteConfirmationMessage: string | null;
  prevalidate: boolean;
}

/**
 * Nested element manager — a `@craftcms/garnish` `Base` port of the legacy
 * jQuery `Craft.NestedElementManager`, orchestrating the server-rendered
 * markup from PHP `NestedElementManager::getCardsHtml()` /
 * `getIndexHtml()`: a card grid (`ul.elements > li > .element`) or an
 * embedded element index, plus the client-built New/Paste buttons and each
 * card's action menu (move/duplicate/copy/paste/delete).
 *
 * Setup lives in {@link init}, invoked from the constructor only for the leaf
 * class (`new.target` guard) — the construction contract shared by every
 * ported module. Modern garnish supplies `Select` (card multi-selection) and
 * `DragSort` (card drag-reordering); everything Craft-widget-shaped stays a
 * jQuery seam (see the `declare` block above).
 *
 * The legacy three-argument signature — `new Craft.NestedElementManager(
 * container, elementType, settings)` — still works for plugin boots; the
 * modern form folds `elementType` into the settings.
 */
export class NestedElementManager extends Base<NestedElementManagerSettings> {
  static defaults: Omit<NestedElementManagerSettings, 'elementType'> = {
    mode: 'cards',
    showInGrid: false,
    ownerElementType: null,
    ownerId: null,
    ownerSiteId: null,
    attribute: null,
    selectable: false,
    sortable: false,
    indexSettings: {},
    canCreate: false,
    canPaste: false,
    minElements: null,
    maxElements: null,
    createButtonLabel: '',
    ownerIdParam: null,
    createAttributes: null,
    pasteAttributes: null,
    fieldId: null,
    fieldHandle: null,
    baseInputName: null,
    deleteLabel: null,
    deleteConfirmationMessage: null,
    bulkDeleteConfirmationMessage: null,
    prevalidate: false,
  };

  /** Resolved in the constructor via `setSettings`, so never null here. */
  declare settings: NestedElementManagerSettings;

  container: HTMLElement;
  elementType: string;

  /** jQuery-wrapped create button(s); a set when grouped create menus render. */
  $createBtn: any = null;
  $pasteBtn: any = null;
  /** Cards mode: the flex container the buttons append into. */
  $btnContainer: any = null;

  // Cards mode
  /** jQuery-wrapped `ul.elements` card list. */
  $elements: any = null;
  elementSort: DragSort | null = null;
  elementSelect: Select | null = null;

  // Index mode
  /** The legacy `Craft.BaseElementIndex` instance. */
  elementIndex: any = null;

  /** The owner form's legacy `Craft.ElementEditor`, if there is one. */
  elementEditor: any = null;
  creatingElement = false;

  /** jQuery create buttons whose `activate` handlers need teardown. */
  #activateBound: any[] = [];
  #afterInitTimeout: ReturnType<typeof setTimeout> | null = null;

  constructor(
    container: HTMLElement | string,
    elementTypeOrSettings?: string | Partial<NestedElementManagerSettings>,
    legacySettings?: Partial<NestedElementManagerSettings>
  ) {
    super();

    // Legacy signature: (container, elementType, settings).
    let settings: Partial<NestedElementManagerSettings>;
    if (typeof elementTypeOrSettings === 'string') {
      settings = {...legacySettings, elementType: elementTypeOrSettings};
    } else {
      settings = elementTypeOrSettings ?? {};
    }

    const resolved =
      typeof container === 'string'
        ? document.querySelector<HTMLElement>(container)
        : container;
    if (!resolved) {
      throw new Error('NestedElementManager: container not found.');
    }
    this.container = resolved;

    this.setSettings(settings, {
      ...NestedElementManager.defaults,
      createButtonLabel: Craft.t('app', 'Create'),
    });
    this.elementType = this.settings.elementType;

    if (new.target === NestedElementManager) {
      this.init();
    }
  }

  init(): void {
    // Double instantiation guard (legacy `.data('nestedElementManager')` check).
    const existing = nestedElementManagerData.get(this.container);
    if (existing) {
      console.warn(
        'Double-instantiating a nested element manager on an element'
      );
      existing.destroy();
    }
    nestedElementManagerData.set(this.container, this);

    if (this.settings.mode === 'cards') {
      if (this.container.querySelector(':scope > .elements')) {
        this.initCards();
      }
    } else {
      this.initElementIndex();
    }

    if (this.settings.canCreate) {
      this.#initCreateButton();
    }

    // The owner's element editor boots after this manager does; look it up a
    // beat later (legacy parity, including the delay).
    this.#afterInitTimeout = setTimeout(() => {
      this.elementEditor = $(this.container)
        .closest('form')
        .data('elementEditor');

      if (this.elementEditor) {
        this.elementEditor.on('update', () => {
          this.settings.ownerId = this.elementEditor.getDraftElementId(
            this.settings.ownerId
          );

          if (this.elementIndex) {
            this.elementIndex.settings.criteria[this.settings.ownerIdParam!] =
              this.settings.ownerId;
          }
        });
      }

      this.trigger('afterInit');
    }, 100);

    // NOTE: `Craft.cp` has no unregister API for this; the callback holds this
    // instance until the page unloads (legacy parity — see README).
    Craft.cp.onCopyElements((elementInfo: any[], buttonLabel?: string) => {
      this.updatePasteButton(elementInfo);
      if (this.$pasteBtn && buttonLabel) {
        this.$pasteBtn.find('.label').text(buttonLabel);
      }
    });
  }

  // --- Buttons ---------------------------------------------------------------

  #initCreateButton(): void {
    let $createBtn = Craft.ui
      .createButton({
        icon: 'plus',
        label: this.settings.createButtonLabel,
        spinner: true,
      })
      .addClass('icon disabled');

    if (this.settings.mode === 'cards') {
      $createBtn.addClass('dashed wrap');
    }

    this.addButton($createBtn);

    if (Array.isArray(this.settings.createAttributes)) {
      const createMenuId = `menu-${Math.floor(Math.random() * 1000000)}`;
      $('<div/>', {
        id: createMenuId,
        class: 'menu menu--disclosure',
        'data-with-search-input':
          this.settings.createAttributes.length > 5 ? 'true' : null,
      }).insertAfter($createBtn);
      $createBtn
        .attr('aria-controls', createMenuId)
        .attr('data-disclosure-trigger', 'true')
        .addClass('menubtn')
        .disclosureMenu();
      const disclosureMenu = $createBtn.data('disclosureMenu');

      // can't use Object.groupBy() here because the group order matters
      const groupedCreateAttributes: Record<string, CreateAttributes[]> = {};
      const groupOrder: string[] = [];
      for (const attributes of this.settings.createAttributes) {
        const group = attributes.group || Craft.t('app', 'General');
        if (!groupedCreateAttributes[group]) {
          groupedCreateAttributes[group] = [];
          groupOrder.push(group);
        }
        groupedCreateAttributes[group].push(attributes);
      }
      const multiGroup = groupOrder.length > 1;

      for (const group of groupOrder) {
        if (multiGroup) {
          disclosureMenu.addHr();
          disclosureMenu.addGroup(group, false);
        }

        for (const attributes of groupedCreateAttributes[group] ?? []) {
          disclosureMenu.addItem({
            icon: attributes.icon ? $(attributes.icon)[0] : null,
            label: attributes.label,
            iconColor: attributes.color,
            onActivate: async () => {
              $createBtn.addClass('loading');
              await this.createElement(attributes.attributes);
              $createBtn.removeClass('loading');
            },
          });
        }
      }

      if (multiGroup && this.settings.mode === 'cards') {
        const $collapsedContainer = $(
          '<div class="expandable-button--collapsed"/>'
        ).insertAfter($createBtn);
        $collapsedContainer.append($createBtn);
        const $expandedContainer = $(
          '<div class="expandable-button--expanded btngroup hidden"/>'
        ).insertAfter($collapsedContainer);

        // Add a SR-only description for each disclosure button
        const btngroupDescriptionId = `btngroup-desc-${Math.floor(
          Math.random() * 100000
        )}`;
        const $btngroupDescription = $('<span>', {
          id: btngroupDescriptionId,
          hidden: true,
          html: Craft.t('app', 'Create {type}', {
            type: typeName(this.elementType, 2),
          }),
        });
        $expandedContainer.append($btngroupDescription);

        groupOrder.forEach((group, i) => {
          const $groupCreateBtn = Craft.ui
            .createButton({
              icon: i === 0 ? 'plus' : null,
              label: group,
              ariaDescribedBy: btngroupDescriptionId,
              spinner: true,
            })
            .addClass('icon disabled dashed')
            .appendTo($expandedContainer);
          const groupCreateMenuId = `menu-${Math.floor(
            Math.random() * 1000000
          )}`;
          $('<div/>', {
            id: groupCreateMenuId,
            class: 'menu menu--disclosure',
          }).appendTo($expandedContainer);
          $groupCreateBtn
            .attr('aria-controls', groupCreateMenuId)
            .attr('data-disclosure-trigger', 'true')
            .addClass('menubtn')
            .disclosureMenu();
          const groupDisclosureMenu = $groupCreateBtn.data('disclosureMenu');

          for (const attributes of groupedCreateAttributes[group] ?? []) {
            groupDisclosureMenu.addItem({
              icon: attributes.icon ? $(attributes.icon)[0] : null,
              label: attributes.label,
              iconColor: attributes.color,
              onActivate: async () => {
                $groupCreateBtn.addClass('loading');
                await this.createElement(attributes.attributes);
                $groupCreateBtn.removeClass('loading');
              },
            });
          }

          $createBtn = $createBtn.add($groupCreateBtn);
        });

        $collapsedContainer.expandableButton();
      }
    } else {
      const onActivate = async (ev: any) => {
        ev.preventDefault();
        $createBtn.addClass('loading');
        await this.createElement(
          this.settings.createAttributes as Record<string, unknown> | null
        );
        $createBtn.removeClass('loading');
      };
      $createBtn.on('activate', onActivate);
      this.#activateBound.push($createBtn);
    }

    this.$createBtn = $createBtn;

    if (this.settings.mode === 'cards') {
      this.updateCreateBtn();
    }
  }

  addButton($button: any): void {
    if (this.settings.mode === 'cards') {
      if (!this.$btnContainer) {
        this.$btnContainer = $('<div/>', {
          class: 'flex flex-inline',
        }).appendTo(this.container);
      }
      $button.appendTo(this.$btnContainer);
      this.updateCreateBtn();
    } else {
      $button.appendTo(this.elementIndex.$toolbar);
    }
  }

  updateCreateBtn(): void {
    if (!this.$createBtn) {
      return;
    }

    if (this.canCreate()) {
      this.$createBtn.removeClass('disabled');
    } else {
      this.$createBtn.addClass('disabled');
    }

    this.updatePasteButton();
  }

  updatePasteButton(elementInfo: any[] | null = null): void {
    elementInfo = elementInfo || Craft.cp.getCopiedElements();
    if (this.canPaste(elementInfo!)) {
      if (!this.$pasteBtn) {
        this.$pasteBtn = Craft.ui.createPasteButton();
        this.addButton(this.$pasteBtn);
        const onActivate = () => this.pasteElements();
        this.$pasteBtn.on('activate', onActivate);
        this.#activateBound.push(this.$pasteBtn);
      } else {
        this.$pasteBtn.removeClass('hidden');
      }
    } else {
      this.$pasteBtn?.addClass('hidden');
    }
  }

  // --- Cards mode ------------------------------------------------------------

  initCards(): void {
    this.$elements = $(this.container).children('.elements');

    // Was .elements just created?
    if (!this.$elements.length) {
      this.$elements = $('<ul/>', {
        class: `elements ${this.settings.showInGrid ? 'card-grid' : 'cards'}`,
      }).prependTo(this.container);
      $(this.container).children('.zilch').addClass('hidden');
    }

    if (this.settings.selectable) {
      this.elementSelect = new Select(
        this.$elements[0],
        this.$elements.children().children('.element').toArray(),
        {
          multi: true,
          vertical: !this.settings.showInGrid,
          filter: (target: EventTarget | null) =>
            !(target instanceof Element
              ? target.closest(
                  'a[href],.toggle,.btn,[role=button],.move,craft-copy-attribute'
                )
              : null),
          checkboxMode: true,
          waitForDoubleClicks: true,
        }
      );
    }

    // only initialise drag-sorting if the device has mouse events
    if (this.settings.sortable && Craft.hasMousePointerEvents()) {
      this.elementSort = new DragSort({
        container: this.$elements[0],
        filter: this.settings.selectable
          ? () => {
              // Only return all the selected items if the target item is selected
              const target = this.elementSort!.$targetItem as unknown as
                | HTMLElement
                | HTMLElement[]
                | null;
              const targetItem = Array.isArray(target) ? target[0] : target;
              if (
                targetItem
                  ?.querySelector(':scope > .element')
                  ?.classList.contains('sel')
              ) {
                return this.elementSelect!.getSelectedItems()
                  .map((element) => element.closest<HTMLElement>('li'))
                  .filter((li): li is HTMLElement => li !== null);
              }
              return targetItem ? [targetItem] : [];
            }
          : null,
        handle:
          '> .element > .card-titlebar > .card-actions-container > .card-actions > .move-btn',
        ignoreHandleSelector: null,
        collapseDraggees: true,
        magnetStrength: 4,
        helperLagBase: 1.5,
        onSortChange: () => {
          this.onSortChange(this.elementSort!.$draggee);
        },
      } as any);
    }

    for (const li of Array.from(this.$elements[0].children)) {
      const element = (li as HTMLElement).querySelector<HTMLElement>(
        ':scope > .element'
      );
      if (element) {
        this.initElement(element);
      }
    }
  }

  deinitCards(): void {
    if (!this.$elements) {
      return;
    }

    this.$elements.remove();
    this.$elements = null;
    this.elementSort?.destroy();
    this.elementSort = null;
    $(this.container).children('.zilch').removeClass('hidden');
  }

  // --- Index mode ------------------------------------------------------------

  initElementIndex(): void {
    this.elementIndex = Craft.createElementIndex(
      this.elementType,
      $(this.container),
      Object.assign(
        {
          context: 'embedded-index',
          sortable: this.settings.sortable,
          prevalidate: this.settings.prevalidate,
        },
        this.settings.indexSettings,
        {
          canDuplicateElements: ($selectedItems: any) => {
            return this.canCreate($selectedItems.length);
          },
          canDeleteElements: ($selectedItems: any) => {
            return this.canDelete($selectedItems.length);
          },
          onBeforeMoveElementsToPage: async () => {
            await this.markAsDirty();
          },
          onMoveElementsToPage: async () => {
            await this.markAsDirty();
          },
          onBeforeReorderElements: async () => {
            await this.markAsDirty();
          },
          onReorderElements: async () => {
            await this.markAsDirty();
          },
          onBeforeDuplicateElements: async () => {
            await this.markAsDirty();
          },
          onDuplicateElements: async () => {
            await this.markAsDirty();
          },
          onBeforeDeleteElements: async () => {
            await this.markAsDirty();
          },
          onDeleteElements: async () => {
            if (!(await this.markAsDirty())) {
              // save the element anyway in case any conditional fields should be shown/hidden
              this.elementEditor?.checkForm(true);
            }
          },
          onBeforeUpdateElements: () => {
            if (this.$createBtn) {
              this.$createBtn.addClass('disabled');
            }
          },
          onCountResults: () => {
            this.updateCreateBtn();
          },
          onSortChange: async ($draggee: any) => {
            await this.onSortChange($draggee);
          },
        }
      )
    );
  }

  // --- Owner-form integration -------------------------------------------------

  async markAsDirty(): Promise<boolean> {
    if (!this.elementEditor || !this.settings.baseInputName) {
      return false;
    }
    return await this.elementEditor.setFormValue(
      this.settings.baseInputName,
      '*'
    );
  }

  async getBaseActionData(): Promise<Record<string, unknown>> {
    // this could end up updating this.settings.ownerId
    await this.markAsDirty();

    return {
      ownerElementType: this.settings.ownerElementType,
      ownerId: this.settings.ownerId,
      ownerSiteId: this.settings.ownerSiteId,
      attribute: this.settings.attribute,
    };
  }

  // --- Sorting ---------------------------------------------------------------

  /**
   * @param draggee - The dragged `li`(s): HTMLElement(s) from the modern card
   * sorter, or a jQuery collection from the (legacy) embedded element index.
   */
  async onSortChange(
    draggee: HTMLElement | HTMLElement[] | any
  ): Promise<void> {
    const $draggee = $(draggee);
    const elementIds = $draggee
      .find('.element')
      .toArray()
      .map((element: HTMLElement) => parseInt($(element).data('id')));

    try {
      const response = await this.updateSortOrder(elementIds);
      Craft.cp.displayNotice(response.data.message);
      if (Craft.broadcaster && this.elementEditor?.settings.draftId) {
        Craft.broadcaster.postMessage({
          pageId: Craft.pageId,
          event: 'reorderNestedElements',
          canonicalId: this.elementEditor.settings.canonicalId,
          draftId: this.elementEditor.settings.draftId,
          isProvisionalDraft: this.elementEditor.settings.isProvisionalDraft,
          elementType: this.elementType,
          elementIds,
        });
      }
    } catch (e: any) {
      Craft.cp.displayError(e?.response?.data?.message);
    }

    if (!(await this.markAsDirty())) {
      // Refresh Live Preview
      Craft.Preview.refresh();
    }
  }

  async updateSortOrder(elementIds: number | number[]): Promise<any> {
    const ids = (Array.isArray(elementIds) ? elementIds : [elementIds]).map(
      (id) => parseInt(String(id))
    );
    const allIds = this.getElementIds();

    const data = Object.assign(await this.getBaseActionData(), {
      elementIds: ids,
      offset: this.getBaseElementOffset() + allIds.indexOf(ids[0]!),
    });

    return await Craft.sendActionRequest('POST', 'nested-elements/reorder', {
      data,
    });
  }

  // --- State queries ----------------------------------------------------------

  bulkActionMode(element: HTMLElement): boolean {
    return (
      (this.elementSelect?.totalSelected ?? 0) > 1 &&
      (this.elementSelect?.isSelected(element) ?? false)
    );
  }

  canCreate(num = 1): boolean {
    if (!this.settings.canCreate || num === 0) {
      return false;
    }

    if (!this.settings.maxElements) {
      return true;
    }

    const total = this.getTotalElements();

    return total !== null && total + num <= this.settings.maxElements;
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars -- legacy call sites pass a count
  canDelete(_num = 1): boolean {
    if (!this.settings.minElements) {
      return true;
    }

    return this.getTotalElements() !== null;
  }

  canPaste(elementInfo: any[]): boolean {
    if (!this.settings.canPaste || !this.canCreate(elementInfo.length)) {
      return false;
    }

    for (const e of elementInfo) {
      if (e.type !== this.elementType) {
        return false;
      }
    }

    if (typeof this.settings.canPaste === 'function') {
      return this.settings.canPaste(elementInfo);
    }

    if (typeof this.settings.canPaste === 'string') {
      // Legacy parity: PHP settings may supply the predicate as source code.
      return eval(this.settings.canPaste)(elementInfo);
    }

    return true;
  }

  getElementIds(): number[] {
    let elements: HTMLElement[];

    if (this.settings.mode === 'cards') {
      elements = this.$elements.find('> li > .element').toArray();
    } else {
      elements = this.elementIndex.view
        .getAllElements()
        .toArray()
        .map((container: HTMLElement) => container.querySelector('.element'));
    }

    return elements
      .map((element) => element?.getAttribute('data-id'))
      .filter((id): id is string => !!id)
      .map((id) => parseInt(id));
  }

  getTotalElements(): number | null {
    if (this.settings.mode === 'cards') {
      return this.$elements ? this.$elements.children().length : 0;
    }

    if (this.elementIndex.isIndexBusy) {
      return null;
    }
    return this.elementIndex.totalUnfilteredResults;
  }

  getBaseElementOffset(): number {
    if (this.settings.mode === 'cards') {
      return 0;
    }

    return this.elementIndex.settings.batchSize * (this.elementIndex.page - 1);
  }

  // --- Element CRUD ----------------------------------------------------------

  async createElement(
    attributes?: Record<string, unknown> | null
  ): Promise<void> {
    if (this.creatingElement) {
      return;
    }
    this.creatingElement = true;

    Craft.cp.announce(Craft.t('app', 'Loading'));

    try {
      await this.markAsDirty();

      const createAttributes = Object.assign(
        {
          elementType: this.elementType,
          ownerId: this.settings.ownerId,
          fieldId: this.settings.fieldId,
          siteId: this.settings.ownerSiteId,
        },
        attributes
      );

      const {data} = await Craft.sendActionRequest('POST', 'elements/create', {
        data: createAttributes,
      });

      const slideout = Craft.createElementEditor(this.elementType, {
        siteId: data.element.siteId,
        elementId: data.element.id,
        draftId: data.element.draftId,
        params: {
          fresh: 1,
        },
      });

      let shownElement = false;

      const showElement = async (element: any) => {
        if (!shownElement) {
          shownElement = true;

          if (this.settings.mode === 'cards') {
            await this.addElementCard(element);
          } else {
            this.elementIndex.clearSearch();
            this.elementIndex.updateElements();
          }

          await this.markAsDirty();
        }
      };

      slideout.on('load', () => {
        slideout.elementEditor.once('afterSaveDraft', () => {
          showElement(data.element);
        });
      });

      slideout.on('submit', async () => {
        await showElement(data.element);
      });

      slideout.on('close', () => {
        if (this.$createBtn) {
          this.$createBtn.filter(':visible:first').focus();
        }

        // save the element in case any conditional fields should be shown/hidden
        this.elementEditor?.checkForm(true);
      });
    } catch (e: any) {
      Craft.cp.displayError(e?.response?.data?.message);
    } finally {
      this.creatingElement = false;
      Craft.cp.announce(Craft.t('app', 'Loading complete'));
    }
  }

  async duplicateElement(element: HTMLElement): Promise<void> {
    const $element = $(element);

    Craft.cp.announce(Craft.t('app', 'Loading'));
    await this.markAsDirty();

    let data;
    try {
      const elementId = $element.data('id');
      const response = await Craft.sendActionRequest(
        'POST',
        'elements/duplicate',
        {
          data: {
            elementType: this.elementType,
            ownerId: this.settings.ownerId,
            siteId: this.settings.ownerSiteId,
            elementId:
              this.elementEditor?.getDraftElementId(elementId) || elementId,
          },
        }
      );
      data = response.data;
    } catch (e: any) {
      Craft.cp.displayError(e?.response?.data?.message);
    }

    const $card = await this.addElementCard(data.element);
    $card.parent().insertAfter($element.parent());
    await this.updateSortOrder(data.element.id);
    // save the element in case any conditional fields should be shown/hidden
    this.elementEditor?.checkForm(true);
  }

  async duplicateElements(elements: HTMLElement[] | any): Promise<void> {
    for (const element of $(elements).toArray()) {
      await this.duplicateElement(element);
    }
  }

  async pasteElements($before: any = null): Promise<void> {
    Craft.cp.announce(Craft.t('app', 'Loading'));
    this.$pasteBtn.addClass('loading');

    try {
      await this.markAsDirty();
      const newElementInfo = await Craft.cp.pasteElements(
        Object.assign(
          {
            primaryOwnerId: this.settings.ownerId,
            ownerId: this.settings.ownerId,
            fieldId: this.settings.fieldId,
            siteId: this.settings.ownerSiteId,
          },
          this.settings.pasteAttributes || {}
        )
      );

      if (!newElementInfo.length) {
        return;
      }

      if (this.settings.mode === 'cards') {
        const $cards = await this.addElementCards(newElementInfo, $before);
        await this.updateSortOrder(newElementInfo[0].id);
        firstFocusableElement($cards[0])?.focus();
      } else {
        this.elementIndex.clearSearch();
        await this.elementIndex.updateElements();
      }
    } finally {
      this.$pasteBtn.removeClass('loading');
    }

    // save the element in case any conditional fields should be shown/hidden
    this.elementEditor?.checkForm(true);
  }

  async deleteElement(element: HTMLElement): Promise<void> {
    const $element = $(element);

    const data = Object.assign(await this.getBaseActionData(), {
      elementId: $element.data('id'),
    });

    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'nested-elements/delete',
        {data}
      );
      Craft.cp.displayNotice(response.data.message);
    } catch (e: any) {
      Craft.cp.displayError(e?.response?.data?.message);
      throw e;
    }

    if (this.settings.sortable) {
      this.elementSort?.removeItems($element[0]);
    }

    $element.parent().remove();

    // :empty isn't reliable due to text nodes
    if (this.$elements.children().length === 0) {
      this.deinitCards();
    }

    if (this.$createBtn) {
      this.updateCreateBtn();
      if (this.canCreate()) {
        this.$createBtn.filter(':visible:first').focus();
      }
    }

    if (!(await this.markAsDirty())) {
      // save the element anyway in case any conditional fields should be shown/hidden
      this.elementEditor?.checkForm(true);
    }
  }

  async deleteElements(elements: HTMLElement[] | any): Promise<void> {
    for (const element of $(elements).toArray()) {
      await this.deleteElement(element);
    }
  }

  // --- Card rendering ---------------------------------------------------------

  async addElementCard(element: any): Promise<any> {
    return await this.addElementCards([element]);
  }

  async addElementCards(elements: any[], $before: any = null): Promise<any> {
    if (this.creatingElement) {
      return null;
    }

    Craft.cp.announce(Craft.t('app', 'Loading'));

    let data;
    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'app/render-elements',
        {
          data: {
            elements: elements.map((element) => ({
              type: this.elementType,
              id: element.id,
              siteId: element.siteId,
              instances: [
                {
                  context: 'field',
                  ui: 'card',
                  sortable: this.settings.sortable,
                  selectable: this.settings.selectable,
                  showActionMenu: true,
                  hyperlink: false,
                },
              ],
            })),
          },
        }
      );
      data = response.data;
    } catch (e: any) {
      Craft.cp.displayError(e?.response?.data?.message);
      throw e?.response?.data?.message ?? e;
    }

    if (!this.$elements) {
      this.initCards();
    }

    let $cards = $();

    for (const elementInfo of elements) {
      for (const card of data.elements[elementInfo.id] || []) {
        const $li = $('<li/>');
        if ($before?.length) {
          $li.insertBefore($before);
        } else {
          $li.appendTo(this.$elements);
        }
        const $card = $(card).appendTo($li);
        $cards = $cards.add($card);
        this.initElement($card[0]);
        Craft.cp.elementThumbLoader.load($card);
      }
    }

    await Craft.appendHeadHtml(data.headHtml);
    await Craft.appendBodyHtml(data.bodyHtml);
    this.updateCreateBtn();

    return $cards;
  }

  // --- Per-card wiring --------------------------------------------------------

  initElement(element: HTMLElement): void {
    const $element = $(element);

    setTimeout(() => {
      if (this.settings.selectable) {
        this.elementSelect!.addItems(element);
      }

      const editable = hasAttr(element, 'data-editable');

      if (editable) {
        // "Edit" button
        const $editBtn = $element.find('.edit-btn');
        if ($editBtn.length) {
          // Override the default event listener
          $editBtn.off('activate');
          $editBtn.on('activate', (ev: any) => {
            // focus on the button so that when the slideout is closed, it's returned to the button
            $editBtn.focus();
            const cpUrl = $element.data('cpUrl');
            if (cpUrl && isCtrlKeyPressed(ev.originalEvent ?? ev)) {
              window.open(cpUrl);
            } else {
              this.createElementEditor(element);
            }
          });
          this.#activateBound.push($editBtn);
        }

        // Double-clicks
        $element.on('dblclick.nem taphold.nem', (ev: any) => {
          if (!$(ev.target).closest('a[href],button,[role=button]').length) {
            this.createElementEditor(element);
          }
        });
      }

      const actionDisclosure = $element
        .find('.action-btn')
        .removeClass('hidden')
        .disclosureMenu()
        .data('disclosureMenu');

      if (actionDisclosure) {
        this.#initElementActionMenu(element, actionDisclosure);
      }
    }, 1);
  }

  #initElementActionMenu(element: HTMLElement, actionDisclosure: any): void {
    const $element = $(element);
    const $actionMenu = actionDisclosure.$container;

    const destructiveGroup = actionDisclosure.getFirstDestructiveGroup();
    let moveUpButton: any,
      moveDownButton: any,
      duplicateButton: any,
      copyButton: any,
      pasteButton: any,
      deleteButton: any;

    const $li = $element.parent();
    const getPrev = () => $li.prev('li');
    const getNext = () => $li.next('li');

    if (this.settings.sortable) {
      this.elementSort?.addItems($li[0]);

      const ul = actionDisclosure.addGroup(null, true, destructiveGroup);

      // Move up/forward
      moveUpButton = actionDisclosure.addItem(
        {
          icon: async () =>
            await Craft.ui.icon(
              this.settings.showInGrid
                ? Craft.orientation === 'ltr'
                  ? 'arrow-left'
                  : 'arrow-right'
                : 'arrow-up'
            ),
          label: this.settings.showInGrid
            ? Craft.t('app', 'Move forward')
            : Craft.t('app', 'Move up'),
          onActivate: () => {
            const $prev = getPrev();
            if ($prev.length) {
              $li.insertBefore($prev);
              this.onSortChange($li);
            }
          },
        },
        ul
      );

      // Move down/backward
      moveDownButton = actionDisclosure.addItem(
        {
          icon: async () =>
            await Craft.ui.icon(
              this.settings.showInGrid
                ? Craft.orientation === 'ltr'
                  ? 'arrow-right'
                  : 'arrow-left'
                : 'arrow-down'
            ),
          label: this.settings.showInGrid
            ? Craft.t('app', 'Move backward')
            : Craft.t('app', 'Move down'),
          onActivate: () => {
            const $next = getNext();
            if ($next.length) {
              $li.insertAfter($next);
              this.onSortChange($li);
            }
          },
        },
        ul
      );
    }

    const duplicatable = hasAttr(element, 'data-duplicatable');
    const copyable = hasAttr(element, 'data-copyable');

    if (duplicatable || copyable) {
      const ul = actionDisclosure.addGroup(null, true, destructiveGroup);

      if (duplicatable) {
        // Duplicate
        duplicateButton = actionDisclosure.addItem(
          {
            icon: async () => await Craft.ui.icon('clone'),
            label: Craft.t('app', 'Duplicate'),
            onActivate: () => {
              if (this.bulkActionMode(element)) {
                this.duplicateElements(this.elementSelect!.getSelectedItems());
              } else {
                this.duplicateElement(element);
              }
            },
          },
          ul
        );
      }

      if (copyable) {
        // Copy
        const $oldCopyBtn = $actionMenu.find('[data-copy-action]');
        if ($oldCopyBtn.length) {
          actionDisclosure.removeItem($oldCopyBtn[0]);
        }

        copyButton = actionDisclosure.addItem(
          {
            icon: async () => await Craft.ui.icon('clone-dashed'),
            iconColor: 'fuchsia',
            label: Craft.t('app', 'Copy'),
            onActivate: () => {
              if (this.bulkActionMode(element)) {
                Craft.cp.copyElements(
                  $(this.elementSelect!.getSelectedItems())
                );
              } else {
                Craft.cp.copyElements($element);
              }
            },
          },
          ul
        );
      }

      // Paste
      pasteButton = actionDisclosure.addItem(
        {
          icon: async () => await Craft.ui.icon('duplicate'),
          iconColor: 'fuchsia',
          label: this.settings.showInGrid
            ? Craft.t('app', 'Paste {type} before', {
                type: typeName(this.elementType, 3),
              })
            : Craft.t('app', 'Paste {type} above', {
                type: typeName(this.elementType, 3),
              }),
          onActivate: async () => {
            if (this.canPaste(Craft.cp.getCopiedElements())) {
              await this.pasteElements($element.parent());
            }
          },
        },
        ul
      );
    }

    if (hasAttr(element, 'data-deletable')) {
      const ul = actionDisclosure.addGroup();
      deleteButton = actionDisclosure.addItem(
        {
          icon: async () => await Craft.ui.icon('trash'),
          label: this.settings.deleteLabel || Craft.t('app', 'Delete'),
          destructive: true,
          onActivate: () => {
            if (this.bulkActionMode(element)) {
              if (confirm(this.settings.bulkDeleteConfirmationMessage!)) {
                this.deleteElements(this.elementSelect!.getSelectedItems());
              }
            } else {
              if (confirm(this.settings.deleteConfirmationMessage!)) {
                this.deleteElement(element);
              }
            }
          },
        },
        ul
      );
    }

    actionDisclosure.on('show', () => {
      const bulk = this.bulkActionMode(element);

      if (moveUpButton) {
        actionDisclosure.toggleItem(moveUpButton, getPrev().length);
      }

      if (moveDownButton) {
        actionDisclosure.toggleItem(moveDownButton, getNext().length);
      }

      if (duplicateButton) {
        actionDisclosure.toggleItem(duplicateButton, this.canCreate());

        $(duplicateButton)
          .children('.menu-item-label')
          .text(
            bulk
              ? Craft.t('app', 'Duplicate selected {type}', {
                  type: typeName(this.elementType, 3),
                })
              : Craft.t('app', 'Duplicate')
          );
      }

      if (copyButton) {
        $(copyButton)
          .children('.menu-item-label')
          .text(
            bulk
              ? Craft.t('app', 'Copy selected {type}', {
                  type: typeName(this.elementType, 3),
                })
              : Craft.t('app', 'Copy')
          );
      }

      const copiedElements = Craft.cp.getCopiedElements();
      const showPasteButton =
        copiedElements.length && this.canPaste(copiedElements);
      actionDisclosure.toggleItem(pasteButton, showPasteButton);
      if (showPasteButton) {
        $(pasteButton)
          .children('.menu-item-label')
          .text(
            this.settings.showInGrid
              ? Craft.t('app', 'Paste {type} before', {
                  type: typeName(
                    this.elementType,
                    copiedElements.length === 1 ? 2 : 3
                  ),
                })
              : Craft.t('app', 'Paste {type} above', {
                  type: typeName(
                    this.elementType,
                    copiedElements.length === 1 ? 2 : 3
                  ),
                })
          );
      }

      if (deleteButton) {
        $(deleteButton)
          .children('.menu-item-label')
          .text(
            bulk
              ? Craft.t('app', 'Delete selected {type}', {
                  type: typeName(this.elementType, 3),
                })
              : Craft.t('app', 'Delete')
          );
      }
    });
  }

  createElementEditor(element: HTMLElement): void {
    const $element = $(element);
    const slideout = Craft.createElementEditor(this.elementType, $element, {
      ownerId: this.elementEditor?.getDraftElementId($element.data('ownerId')),
      onLoad: () => {
        slideout.elementEditor.on('update', () => {
          Craft.Preview.refresh();
        });
      },
      onBeforeSubmit: async () => {
        // If the nested element is primarily owned by the same owner element it was queried for,
        // then ensure we're working with a draft and save the nested element changes to the draft
        // note: this workflow doesn't apply to elements nested directly in global sets as globals don't use element editor
        if (
          typeof this.elementEditor !== 'undefined' &&
          this.elementEditor !== null &&
          hasAttr(element, 'data-owner-is-canonical') &&
          !hasAttr(element, 'data-is-unpublished-draft') &&
          !this.elementEditor.settings.isUnpublishedDraft
        ) {
          await slideout.elementEditor.checkForm(true, true);
          await this.markAsDirty();
          if (
            this.elementEditor.settings.draftId &&
            slideout.elementEditor.settings.draftId
          ) {
            if (!slideout.elementEditor.settings.saveParams) {
              slideout.elementEditor.settings.saveParams = {};
            }
            slideout.elementEditor.settings.saveParams.action =
              'elements/save-nested-element-for-derivative';
            slideout.elementEditor.settings.saveParams.newOwnerId =
              this.settings.ownerId;
          }
        }
      },
      onSubmit: (ev: any) => {
        if (ev.data.id != $element.data('id')) {
          // swap the element with the new one
          $element
            .attr('data-id', ev.data.id)
            .data('id', ev.data.id)
            .data('owner-id', ev.data.ownerId);
          Craft.refreshElementInstances(ev.data.id);
        }
      },
    });
  }

  // --- Teardown ---------------------------------------------------------------

  override destroy(): void {
    if (this.#afterInitTimeout !== null) {
      clearTimeout(this.#afterInitTimeout);
      this.#afterInitTimeout = null;
    }

    for (const $bound of this.#activateBound) {
      $bound.off('activate');
    }
    this.#activateBound = [];
    this.$elements?.children().children('.element').off('.nem');

    this.elementSort?.destroy();
    this.elementSort = null;
    this.elementSelect?.destroy();
    this.elementSelect = null;

    nestedElementManagerData.delete(this.container);
    super.destroy();
  }
}
