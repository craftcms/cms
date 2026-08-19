import {
  BACKSPACE_KEY,
  Base,
  bod,
  CustomSelect,
  DELETE_KEY,
  DOWN_KEY,
  DragSort,
  firstFocusableElement,
  hasAttr,
  RETURN_KEY,
  Select,
  UP_KEY,
} from '@craftcms/garnish';
import {elementSelectInputData} from './support';

declare const Craft: any;
declare const $: any;

const noop = () => {};

/**
 * Settings defaults that don't rely on runtime globals.
 * Craft.t() labels are resolved lazily in init() so Craft is available.
 */
const DEFAULTS = {
  id: null as string | null,
  name: null as string | null,
  fieldId: null as number | null,
  elementType: null as string | null,
  sources: null as string[] | null,
  condition: null as any,
  referenceElementId: null as number | null,
  referenceElementOwnerId: null as number | null,
  referenceElementSiteId: null as number | null,
  criteria: {} as Record<string, any>,
  searchCriteria: null as Record<string, any> | null,
  allowAdd: true,
  allowRemove: true,
  allowSelfRelations: false,
  sourceElementId: null as number | null,
  disabledElementIds: null as number[] | null,
  viewMode: 'list',
  single: false,
  maintainHierarchy: false,
  branchLimit: null as number | null,
  limit: null as number | null,
  defaultPlacement: 'end',
  showSiteMenu: false,
  siteIds: null as number[] | null,
  modalStorageKey: null as string | null,
  modalSettings: {} as Record<string, any>,
  onAddElements: noop,
  onSelectElements: noop,
  onRemoveElements: noop,
  sortable: true,
  selectable: true,
  showActionMenu: true,
  editable: true,
  prevalidate: false,
  editorSettings: {} as Record<string, any>,
};

/**
 * BaseElementSelectInput — a port of `Craft.BaseElementSelectInput` onto the
 * modern `@craftcms/garnish` `Base`. Manages a relational field's element list:
 * drag-sort, element selection, search-as-you-type, and modal element picker.
 *
 * Seam notes:
 * - The "Add" button uses `$(btn).on('activate', ...)` because `activate` is a
 *   jQuery synthetic event; `addListener` (native addEventListener) would miss
 *   jQuery-triggered activation.
 * - `taphold` on element items likewise uses `$(el).on('taphold', ...)`.
 * - `keydown` on element items uses `addListener` (native event).
 * - `Garnish.Select` / `DragSort` / `CustomSelect` are imported from the
 *   modern @craftcms/garnish package.
 * - Booted via `new Craft.BaseElementSelectInput({id, …})` from PHP templates,
 *   so there is no custom element — the class is registered on `window.Craft`
 *   via `registerCraftGlobals`.
 */
export class BaseElementSelectInput extends Base {
  declare settings: any;

  static readonly ADD_FX_DURATION = 200;
  static readonly REMOVE_FX_DURATION = 200;
  static defaults: Record<string, any> = DEFAULTS;

  elementSelect: any = null;
  elementSort: any = null;
  modal: any = null;
  elementEditor: any = null;
  modalFirstOpen = true;

  $container: any = null;
  $form: any = null;
  $elementsContainer: any = null;
  $elements: any = null;
  $addElementBtn: any = null;
  $spinner: any = null;

  searchTimeout: ReturnType<typeof setTimeout> | null = null;
  searchMenu: any = null;
  $searchContainer: any = null;
  $searchInput: any = null;
  $searchSpinner: any = null;

  modalStorageKey: string | null = null;

  _$replaceElement: any = null;
  _ignoreSearchBlur = false;
  _initialized = false;

  #handleShowElementEditor: ((ev: any) => void) | null = null;

  constructor(settings?: any) {
    super();
    if (new.target === BaseElementSelectInput) {
      this.init(settings);
    }
  }

  get thumbLoader(): any {
    console.warn(
      'Craft.BaseElementSelectInput::thumbLoader is deprecated. Craft.cp.elementThumbLoader should be used instead.'
    );
    return Craft.cp.elementThumbLoader;
  }

  init(...initArgs: any[]): void {
    // Legacy compat: positional arguments were once accepted.
    let settings = initArgs[0];
    if (!$.isPlainObject(settings)) {
      const argNames = [
        'id',
        'name',
        'elementType',
        'sources',
        'criteria',
        'sourceElementId',
        'limit',
        'modalStorageKey',
        'fieldId',
      ];
      const normalized: Record<string, any> = {};
      for (let i = 0; i < argNames.length; i++) {
        if (typeof initArgs[i] !== 'undefined') {
          normalized[argNames[i]!] = initArgs[i];
        } else {
          break;
        }
      }
      settings = normalized;
    }

    this.settings = Object.assign(
      {},
      BaseElementSelectInput.defaults,
      settings
    );

    if (this.settings.modalStorageKey) {
      this.modalStorageKey =
        'BaseElementSelectInput.' + this.settings.modalStorageKey;
    }

    if (this.settings.limit == 1 || this.settings.maintainHierarchy) {
      this.settings.sortable = false;
    }

    this.$container = this.getContainer();
    elementSelectInputData.set(this.$container[0], this);
    this.$form = this.$container.closest('form');
    this.$container.data('elementSelect', this);

    this.$elementsContainer = this.getElementsContainer();
    this.$addElementBtn = this.getAddElementsBtn();
    this.$spinner = this.getSpinner();
    this.$searchContainer = this.getSearchContainer();
    this.$searchInput = this.getSearchInput();
    this.$searchSpinner = this.getSearchSpinner();

    this.initElementSelect();
    this.initElementSort();
    this.initSearch();
    this.resetElements();

    if (this.$addElementBtn.length) {
      // activate is a jQuery synthetic event — must use jQuery .on(), not addListener.
      $(this.$addElementBtn).on('activate.elementSelectInput', () =>
        this.showModal()
      );
    }

    requestAnimationFrame(() => {
      this._initialized = true;
    });

    if (this.elementSelect) {
      this.addListener(window, 'mousedown', ((ev: MouseEvent) => {
        const target = ev.target as Element;
        if (
          !this.$container.is(target) &&
          !this.$container.find(target).length &&
          !$(target).closest('.menu').length
        ) {
          this.elementSelect.deselectAll();
        }
      }) as any);
    }

    setTimeout(() => {
      this.elementEditor = this.$container
        .closest('form')
        .data('elementEditor');
    }, 100);
  }

  override destroy(): void {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = null;
    }

    if (this.searchMenu) {
      this.killSearchMenu();
    }

    this.$addElementBtn?.off('.elementSelectInput');
    if (this.#handleShowElementEditor) {
      this.$elements?.off('taphold', this.#handleShowElementEditor);
      this.#handleShowElementEditor = null;
    }

    this.elementSort?.destroy?.();
    this.elementSort = null;
    this.elementSelect?.destroy?.();
    this.elementSelect = null;
    this.modal?.destroy?.();
    this.modal = null;

    if (this.$container?.[0]) {
      elementSelectInputData.delete(this.$container[0]);
    }

    super.destroy();
  }

  get totalSelected(): number {
    return this.$elements.length;
  }

  getContainer(): any {
    return $('#' + this.settings.id);
  }

  getElementsContainer(): any {
    return this.$container.children('.elements');
  }

  getElements(): any {
    if (this.$elementsContainer.hasClass('structure')) {
      return this.$elementsContainer.find('> li .row .element');
    } else {
      return this.$elementsContainer.find('> li > .element');
    }
  }

  getSearchContainer(): any {
    return this.$container.find('.elementselect__search-input-wrapper');
  }

  getSearchInput(): any {
    return this.$searchContainer.find('input');
  }

  getSearchSpinner(): any {
    return this.$searchContainer.find('.spinner');
  }

  getAddElementsBtn(): any {
    return this.$container.find('[command="--add-element"]');
  }

  getSpinner(): any {
    return this.$container.find('.spinner');
  }

  initElementSelect(): void {
    if (this.settings.selectable) {
      this.elementSelect = new Select(this.elementSelectSettings());
    }
  }

  elementSelectSettings(): any {
    return {
      multi: this.settings.sortable,
      filter: (target: Element) => {
        return !$(target).closest('a[href],button,[role=button]').length;
      },
      makeFocusable: false,
    };
  }

  initElementSort(): void {
    if (!Craft.hasMousePointerEvents()) {
      $(
        '.element > .chip-content > .chip-actions > .move-btn, .element > .card-titlebar > .card-actions-container > .card-actions > .move-btn'
      ).hide();
    }

    if (this.settings.sortable && Craft.hasMousePointerEvents()) {
      this.elementSort = new DragSort({
        container: this.$elementsContainer,
        filter: this.settings.selectable
          ? () => {
              if (
                this.elementSort.$targetItem
                  .children('.element')
                  .hasClass('sel')
              ) {
                return this.elementSelect.getSelectedItems().parent('li');
              } else {
                return this.elementSort.$targetItem;
              }
            }
          : null,
        ignoreHandleSelector: '.delete',
        handle: (() => {
          switch (this.settings.viewMode) {
            case 'list':
            case 'list-inline':
            case 'thumbs':
            case 'large':
              return '> .element > .chip-content > .chip-actions > .move-btn';
            case 'cards':
              return '> .element > .card-titlebar > .card-actions-container > .card-actions > .move-btn';
            default:
              return null;
          }
        })(),
        axis: this.getElementSortAxis() as 'x' | 'y' | null,
        collapseDraggees: true,
        magnetStrength: 4,
        helperLagBase: 1.5,
        onBeforeDragStart: () => {
          this.elementEditor?.pause();
          // Disable the labels' overflow tooltips while dragging so they
          // don't recompute/pop up during the drag.
          this.$elementsContainer.find('craft-truncate').attr('disabled', true);
        },
        onDragStop: () => {
          this.elementEditor?.resume();
          this.$elementsContainer.find('craft-truncate').removeAttr('disabled');
        },
        onSortChange: () => {
          this.onSortChange();
        },
      });
    }
  }

  getElementSortAxis(): string | null {
    if (
      this.settings.viewMode === 'list' &&
      !this.getElementsContainer().hasClass('inline-chips')
    ) {
      return 'y';
    }
    return null;
  }

  canAddMoreElements(): boolean {
    return (
      this.settings.allowAdd &&
      (!this.settings.limit || this.$elements.length < this.settings.limit)
    );
  }

  updateAddElementsBtn(): void {
    if (this.canAddMoreElements()) {
      this.enableAddElementsBtn();
    } else {
      this.disableAddElementsBtn();
    }
  }

  enableAddElementsBtn(): void {
    if (this.settings.allowAdd && this.$addElementBtn.length) {
      this.$addElementBtn.removeClass('hidden');
      this.$searchContainer.removeClass('hidden');
    }
    this.updateButtonContainer();
  }

  disableAddElementsBtn(): void {
    if (this.$addElementBtn.length) {
      this.$addElementBtn.addClass('hidden');
      this.$searchContainer.addClass('hidden');
    }
    this.updateButtonContainer();
  }

  showSpinner(): void {
    this.$spinner?.removeClass('hidden');
    this.updateButtonContainer();
  }

  hideSpinner(): void {
    this.$spinner?.addClass('hidden');
    this.updateButtonContainer();
  }

  updateButtonContainer(): void {
    const $container =
      this.$addElementBtn.length && this.$addElementBtn.parent('.flex');
    if ($container?.length) {
      if ($container.children(':not(.hidden)').length) {
        $container.removeClass('hidden');
      } else {
        $container.addClass('hidden');
      }
    }
  }

  getNextLogicalFocusElement(): any {
    if (!this.canAddMoreElements()) {
      return this.getLastActionBtn();
    }

    if (
      this.$searchContainer.length &&
      !this.$searchContainer.hasClass('hidden')
    ) {
      return this.$searchInput;
    }

    if (this.$addElementBtn.length && !this.$addElementBtn.hasClass('hidden')) {
      return this.$addElementBtn;
    }

    return this.$container.attr('tabindex', '-1');
  }

  focusNextLogicalElement(): void {
    const $focusTarget = this.getNextLogicalFocusElement();
    if ($focusTarget?.length) {
      $focusTarget.focus();
    }
  }

  /** @deprecated */
  focusLastRemoveBtn(): void {
    this.focusLastActionBtn();
  }

  getLastActionBtn(): any {
    return this.$container.find('.action-btn').last();
  }

  focusLastActionBtn(): void {
    this.getLastActionBtn().focus();
  }

  resetElements(): void {
    if (this.$elements !== null) {
      this.removeElements(this.$elements);
    } else {
      this.$elements = $();
    }
    this.addElements(this.getElements());
  }

  addElements($elements: any): void {
    for (let i = 0; i < $elements.length; i++) {
      const $element = $elements.eq(i);
      const actions = this.defineElementActions($element);

      if (actions.length) {
        Craft.addActionsToChip($element, actions);

        const updateMoveButtons = () => {
          const $li = $element.parent();
          const $prev = $li.prev();
          const $next = $li.next();

          return {showForward: !!$prev.length, showBackward: !!$next.length};
        };

        // The common case (`ElementHtml::componentActionMenu()`): a modern
        // `<craft-action-menu>`, whose `craft-action-item`s aren't jQuery
        // disclosure-menu items, so there's no `disclosureMenu` data to read.
        const actionMenu = $element.find('craft-action-menu').first().get(0);

        if (actionMenu) {
          const moveForwardItem = actionMenu.querySelector(
            '[data-move-forward]'
          );
          const moveBackwardItem = actionMenu.querySelector(
            '[data-move-backward]'
          );

          // No public "opening" event on `<craft-action-menu>` to defer to
          // (mirroring the disclosure-menu `show` handler below), so watch
          // its reflected `opened` attribute instead.
          const observer = new MutationObserver(() => {
            if (!(actionMenu as any).opened) {
              return;
            }

            const {showForward, showBackward} = updateMoveButtons();
            moveForwardItem?.toggleAttribute('hidden', !showForward);
            moveBackwardItem?.toggleAttribute('hidden', !showBackward);
          });
          observer.observe(actionMenu, {
            attributes: true,
            attributeFilter: ['opened'],
          });
        } else {
          // An old-markup, or modern-chip-with-legacy-trigger, disclosure menu.
          const disclosureMenu =
            $element
              .find(
                '> .chip-content > .chip-actions .action-btn, > .card-titlebar > .card-actions-container > .card-actions .action-btn'
              )
              .data('disclosureMenu') ||
            $element
              .children('[slot="suffix"]')
              .find('[data-disclosure-trigger]')
              .first()
              .data('disclosureMenu');

          if (disclosureMenu) {
            const moveForwardBtn = disclosureMenu.$container.find(
              '[data-move-forward]'
            )[0];
            const moveBackwardBtn = disclosureMenu.$container.find(
              '[data-move-backward]'
            )[0];

            disclosureMenu.on('show', () => {
              const {showForward, showBackward} = updateMoveButtons();

              if (moveForwardBtn) {
                disclosureMenu.toggleItem(moveForwardBtn, showForward);
              }
              if (moveBackwardBtn) {
                disclosureMenu.toggleItem(moveBackwardBtn, showBackward);
              }
            });
          }
        }
      }

      if (this.settings.sortable && Craft.hasMousePointerEvents()) {
        Craft.ui
          .createButton({
            class: 'chromeless small move-btn',
            icon: 'move',
          })
          .attr({
            title: Craft.t('app', 'Reorder'),
            'aria-label': Craft.t('app', 'Reorder'),
            'aria-describedby': $element.find('.label').attr('id'),
          })
          .appendTo(
            $element.find(
              '> .chip-content > .chip-actions, > .card-titlebar > .card-actions-container > .card-actions'
            )
          );
      }
    }

    Craft.cp.elementThumbLoader.load($elements);

    if (this.settings.selectable) {
      this.elementSelect.addItems($elements);
    }

    if (this.settings.sortable) {
      this.elementSort?.addItems($elements.parent('li'));
    }

    if (this.settings.editable) {
      this.#handleShowElementEditor = (ev: any) => {
        if (ev.type === 'taphold' && ev.target.nodeName === 'BUTTON') {
          return;
        }
        const $element = $(ev.currentTarget);
        if (
          hasAttr($element[0], 'data-editable') &&
          !$element.hasClass('disabled') &&
          !$element.hasClass('loading')
        ) {
          this.createElementEditor($element);
        }
      };

      // dblclick is a native event — addListener works.
      this.addListener(
        $elements.toArray(),
        'dblclick',
        this.#handleShowElementEditor
      );

      // taphold is a jQuery synthetic event — must use jQuery .on().
      if ($.isTouchCapable()) {
        $elements.on('taphold', this.#handleShowElementEditor);
      }
    }

    // keydown is native — addListener works.
    $elements.each((_: number, el: Element) => {
      this.addListener(el, 'keydown', ((ev: KeyboardEvent) => {
        if ([BACKSPACE_KEY, DELETE_KEY].includes(ev.keyCode)) {
          ev.stopPropagation();
          ev.preventDefault();
          const $selected = this.elementSelect?.getSelectedItems() ?? $();
          for (let i = 0; i < $selected.length; i++) {
            this.removeElement($selected.eq(i));
          }
        }
      }) as any);
    });

    this.$elements = this.$elements.add($elements);
    this.updateAddElementsBtn();
    this.onAddElements();
    this.onSortChange();
  }

  defineElementActions($element: any): any[] {
    const actions: any[] = [];

    if (this.settings.sortable) {
      const axis = this.getElementSortAxis();
      actions.push({
        icon: async () =>
          await Craft.ui.icon(
            axis === 'y'
              ? 'arrow-up'
              : Craft.orientation === 'ltr'
                ? 'arrow-left'
                : 'arrow-right'
          ),
        label:
          axis === 'y'
            ? Craft.t('app', 'Move up')
            : Craft.t('app', 'Move forward'),
        callback: () => {
          this.moveElementForward($element);
        },
        attributes: {'data-move-forward': true},
      });
      actions.push({
        icon: async () =>
          await Craft.ui.icon(
            axis === 'y'
              ? 'arrow-down'
              : Craft.orientation === 'ltr'
                ? 'arrow-right'
                : 'arrow-left'
          ),
        label:
          axis === 'y'
            ? Craft.t('app', 'Move down')
            : Craft.t('app', 'Move backward'),
        callback: () => {
          this.moveElementBackward($element);
        },
        attributes: {'data-move-backward': true},
      });
    }

    if (this.settings.allowRemove) {
      if (this.settings.elementType) {
        actions.push({
          icon: async () => await Craft.ui.icon('arrows-rotate'),
          label: Craft.t('app', 'Replace'),
          callback: () => {
            this._$replaceElement = $element;
            this.showModal();
          },
        });
      }

      actions.push({
        icon: async () => await Craft.ui.icon('remove'),
        label: Craft.t('app', 'Remove'),
        callback: () => {
          if (this.elementSelect?.isSelected($element)) {
            this.removeElement(this.elementSelect.getSelectedItems());
          } else {
            this.removeElement($element);
          }
        },
      });
    }

    return actions;
  }

  createElementEditor($element: any, settings?: any): any {
    settings = Object.assign(
      {
        elementSelectInput: this,
        prevalidate: this.settings.prevalidate,
      },
      settings
    );
    return Craft.createElementEditor(
      this.settings.elementType,
      $element,
      settings
    );
  }

  replaceElement(elementId: number, replacementId: number): Promise<void> {
    return new Promise((resolve, reject) => {
      const $existing = this.$elements.filter(`[data-id="${elementId}"]`);

      if (!$existing.length) {
        reject(`No element selected with an ID of ${elementId}.`);
        return;
      }

      this.showSpinner();

      Craft.sendActionRequest('POST', 'app/render-elements', {
        data: {
          elements: [
            {
              type: this.settings.elementType,
              id: replacementId,
              siteId: this.settings.criteria.siteId,
              instances: [
                {
                  context: 'field',
                  ui: ['list', 'list-inline', 'thumbs', 'large'].includes(
                    this.settings.viewMode
                  )
                    ? 'chip'
                    : 'card',
                  size: ['thumbs', 'large'].includes(this.settings.viewMode)
                    ? 'large'
                    : 'small',
                  showActionMenu: this.settings.showActionMenu,
                },
              ],
            },
          ],
        },
      })
        .then(async ({data}: any) => {
          this.removeElement($existing);
          const elementInfo = Craft.getElementInfo(
            data.elements[replacementId][0]
          );
          this.selectElements([elementInfo]).then(resolve);
          await Craft.appendHeadHtml(data.headHtml);
          await Craft.appendBodyHtml(data.bodyHtml);
        })
        .catch((e: any) => {
          Craft.cp.displayError(e?.response?.data?.message);
          reject(e?.response?.data?.message);
        })
        .finally(() => {
          this.hideSpinner();
        });
    });
  }

  onSortChange(): void {
    this.elementSelect?.resetItemOrder();
    this.$elements = $().add(this.$elements);
  }

  moveElementForward($element: any): void {
    const $li = $element.closest('li');
    const $prev = $li.prev();
    if ($prev.length) {
      $li.insertBefore($prev);
      this.onSortChange();
    }
  }

  moveElementBackward($element: any): void {
    const $li = $element.closest('li');
    const $next = $li.next();
    if ($next.length) {
      $li.insertAfter($next);
      this.onSortChange();
    }
  }

  /**
   * Removes elements from the field value without removing their DOM nodes.
   */
  removeElements($elements: any): void {
    if (this.settings.selectable) {
      this.elementSelect.removeItems($elements);
    }

    if (this.modal) {
      const ids: number[] = [];
      for (let i = 0; i < $elements.length; i++) {
        const id = $elements.eq(i).data('id');
        if (id) ids.push(id);
      }
      if (ids.length) {
        this.modal.elementIndex.enableElementsById(ids);
      }
    }

    $elements.children('input').prop('disabled', true);

    let $nextElement: any, $prevElement: any;
    if (this.settings.selectable) {
      const lastIdx = this.$elements.index($elements.last());
      $nextElement = this.$elements.eq(lastIdx + 1);
      $prevElement = lastIdx - 1 >= 0 ? this.$elements.eq(lastIdx - 1) : null;
    }

    if ($nextElement?.length || $prevElement?.length) {
      const $target = $nextElement?.length
        ? this.getElementFocusTarget($nextElement)
        : this.getElementFocusTarget($prevElement);
      $target?.focus();
    } else {
      setTimeout(() => {
        this.focusNextLogicalElement();
      }, 200);
    }

    this.$elements = this.$elements.not($elements);
    this.updateAddElementsBtn();
    this.onSortChange();
    this.onRemoveElements();
  }

  getElementFocusTarget($element: any): any {
    if ($element.is(':focusable')) {
      return $element;
    }
    const $first = firstFocusableElement($element[0]);
    return $first ?? null;
  }

  /**
   * Completely removes element(s) from the UI and field value.
   */
  removeElement($elements: any): void {
    if (this.settings.maintainHierarchy) {
      let $descendants = $();
      for (let i = 0; i < $elements.length; i++) {
        $descendants = $descendants.add(
          $elements.eq(i).parent().siblings('ul').find('.element')
        );
      }
      $elements = $elements.add($descendants);
    }

    $('[name]', $elements).removeAttr('name');
    this.removeElements($elements);

    if (this.settings.maintainHierarchy) {
      for (let i = 0; i < $elements.length; i++) {
        this._animateStructureElementAway($elements, i);
      }
    } else {
      for (let i = 0; i < $elements.length; i++) {
        const $element = $elements.eq(i);
        const $li = $element.parent('li');
        this.animateElementAway($element);
        $li.remove();
      }
    }
  }

  async animateElementAway(
    $element: any,
    callback?: () => void
  ): Promise<void> {
    const offset = $element.offset();
    const width = $element.width();

    $element.appendTo($(bod)).css({
      'z-index': 0,
      position: 'absolute',
      top: offset.top,
      left: offset.left,
      maxWidth: width + 'px',
    });

    await Craft.animate($element, {
      opacity: -1,
      left: offset.left + 100 * (Craft.orientation === 'ltr' ? -1 : 1),
    });

    $element.remove();
    callback?.();
  }

  showModal(): void {
    if (!this._$replaceElement && !this.canAddMoreElements()) {
      return;
    }

    if (!this.modal) {
      this.modal = this.createModal();
      this.modalFirstOpen = false;
    } else {
      this.modal.show();
    }
  }

  createModal(): any {
    return Craft.createElementSelectorModal(
      this.settings.elementType,
      this.getModalSettings()
    );
  }

  getModalSettings(): any {
    const settings = Object.assign(
      {
        closeOtherModals: false,
        storageKey: this.modalStorageKey,
        sources: this.settings.sources,
        condition: this.settings.condition,
        referenceElementId: this.settings.referenceElementId
          ? this.elementEditor?.getDraftElementId(
              this.settings.referenceElementId
            ) || this.settings.referenceElementId
          : null,
        referenceElementOwnerId: this.settings.referenceElementOwnerId
          ? this.elementEditor?.getDraftElementId(
              this.settings.referenceElementOwnerId
            ) || this.settings.referenceElementOwnerId
          : null,
        referenceElementSiteId: this.settings.referenceElementSiteId,
        criteria: Object.assign({}, this.settings.criteria),
        multiSelect: this.settings.limit != 1,
        hideOnSelect: false,
        showSiteMenu: this.settings.showSiteMenu,
        siteIds: this.settings.siteIds,
        disabledElementIds: this.getDisabledElementIds(),
        onSelect: this.onModalSelect.bind(this),
        onHide: this.onModalHide.bind(this),
        triggerElement: () => this.getNextLogicalFocusElement(),
        modalTitle: Craft.t('app', 'Choose'),
      },
      this.settings.modalSettings
    );

    if (!this.modalFirstOpen) {
      settings.preferStoredSource = true;
    }

    return settings;
  }

  getSelectedElementIds(): number[] {
    const ids: number[] = [];
    for (let i = 0; i < this.$elements.length; i++) {
      if (
        this.settings.modalSettings.matchSiteBeforeDisablingElement &&
        this.settings.modalSettings.siteId
      ) {
        if (
          this.$elements.eq(i).data('siteId') ==
          this.settings.modalSettings.siteId
        ) {
          ids.push(this.$elements.eq(i).data('id'));
        }
      } else {
        ids.push(this.$elements.eq(i).data('id'));
      }
    }
    return ids;
  }

  getDisabledElementIds(): number[] {
    const ids = this.getSelectedElementIds();

    if (!this.settings.allowSelfRelations && this.settings.sourceElementId) {
      ids.push(this.settings.sourceElementId);
    }

    if (this.settings.disabledElementIds) {
      ids.push(...this.settings.disabledElementIds);
    }

    return ids;
  }

  async onModalSelect(elements: any[]): Promise<void> {
    this.modal?.disable();
    this.modal?.disableCancelBtn();
    this.modal?.disableSelectBtn();
    this.modal?.showFooterSpinner();

    this.elementEditor?.pause();

    if (this._$replaceElement) {
      this.removeElement(this._$replaceElement);
      this._$replaceElement = null;
    }

    const [inputUiType, inputUiSize] = (() => {
      switch (this.settings.viewMode) {
        case 'thumbs':
        case 'large':
          return ['chip', 'large'] as const;
        case 'cards':
        case 'cards-grid':
          return ['card', null] as const;
        default:
          return ['chip', 'small'] as const;
      }
    })();

    const {data} = await Craft.sendActionRequest(
      'POST',
      'app/render-elements',
      {
        data: {
          elements: [
            {
              type: this.settings.elementType,
              id: elements.map((e) => e.id),
              siteId: elements[0].siteId,
              instances: [
                {
                  context: 'field',
                  ui: inputUiType,
                  size: inputUiSize,
                  showActionMenu: this.settings.showActionMenu,
                },
              ],
            },
          ],
        },
      }
    );

    for (let i = 0; i < elements.length; i++) {
      if (typeof data.elements[elements[i].id] !== 'undefined') {
        elements[i].$modalElement = elements[i].$element;
        elements[i].$element = $(data.elements[elements[i].id][0]);
      }
    }

    if (this.settings.maintainHierarchy) {
      await this.selectStructuredElements(elements);
    } else {
      if (this.settings.limit) {
        const slotsLeft = this.settings.limit - this.$elements.length;
        if (elements.length > slotsLeft) {
          elements = elements.slice(0, slotsLeft);
        }
      }

      await this.selectElements(elements);
      this.updateDisabledElementsInModal();
    }

    this.modal?.enable();
    this.modal?.enableCancelBtn();
    this.modal?.enableSelectBtn();
    this.modal?.hideFooterSpinner();
    this.modal?.hide();

    await Craft.appendHeadHtml(data.headHtml);
    await Craft.appendBodyHtml(data.bodyHtml);

    this.elementEditor?.resume();
  }

  onModalHide(): void {
    if (
      this.modal &&
      this.settings.condition &&
      this.settings.referenceElementId
    ) {
      this.modal.destroy();
      this.modal = null;
    }
    this._$replaceElement = null;
  }

  async selectElements(elements: any[]): Promise<void> {
    const animateElements: any[] = [];

    for (const elementInfo of elements) {
      const $inputElement = this.createNewElement(elementInfo);
      this.appendElement($inputElement);
      this.addElements($inputElement);
      elementInfo.$element = $inputElement;

      const $modalElement = elementInfo.$modalElement || elementInfo.$element;
      if ($modalElement?.parent().length) {
        animateElements.push({$modalElement, $inputElement});
      }
    }

    this.animateElementsIntoPlace(animateElements);
    this.onSelectElements(elements);
  }

  async selectStructuredElements(elements: any[]): Promise<void> {
    const selectedElementIds =
      this.settings.branchLimit == 1 ? [] : this.getSelectedElementIds();

    for (const el of elements) {
      selectedElementIds.push(el.id);
    }

    const data = {
      elementIds: selectedElementIds,
      siteId: elements[0].siteId,
      containerId: this.settings.id,
      name: this.settings.name,
      branchLimit: this.settings.branchLimit,
      selectionLabel: this.settings.selectionLabel,
      elementType: this.settings.elementType,
    };

    const response = await Craft.sendActionRequest(
      'POST',
      'relational-fields/structured-input-html',
      {data}
    );

    const $newInput = $(response.data.html);
    const $newElementsContainer = $newInput.children('.elements');

    this.$elementsContainer.replaceWith($newElementsContainer);
    this.$elementsContainer = $newElementsContainer;

    await Craft.appendHeadHtml(response.data.headHtml);
    await Craft.appendBodyHtml(response.data.bodyHtml);

    this.resetElements();

    const filteredElements: any[] = [];
    const animateElements: any[] = [];

    for (const element of elements) {
      const $inputElement = this.getElementById(element.id);
      if ($inputElement) {
        filteredElements.push(element);
        animateElements.push({
          $modalElement: element.$element,
          $inputElement,
        });
      }
    }

    this.animateElementsIntoPlace(animateElements);
    this.updateDisabledElementsInModal();
    this.onSelectElements(filteredElements);
  }

  createNewElement(elementInfo: any): any {
    const $element = elementInfo.$element.clone();
    Craft.setElementSize(
      $element,
      ['thumbs', 'large'].includes(this.settings.viewMode) ? 'large' : 'small'
    );
    $element.addClass('removable').append(
      $('<input/>', {
        type: 'hidden',
        name: this.settings.name + (this.settings.single ? '' : '[]'),
        value: elementInfo.id,
      })
    );
    return $element;
  }

  appendElement($element: any): void {
    const $li = $('<li/>').append($element);
    if (this.settings.defaultPlacement === 'beginning') {
      $li.prependTo(this.$elementsContainer);
    } else {
      $li.appendTo(this.$elementsContainer);
    }
  }

  async animateElementIntoPlace(
    $modalElement: any,
    $inputElement: any
  ): Promise<void> {
    await this.animateElementsIntoPlace([{$modalElement, $inputElement}]);
  }

  async animateElementsIntoPlace(
    elements: Array<{$modalElement: any; $inputElement: any}>
  ): Promise<void> {
    const animations: any[] = [];

    for (const {$modalElement, $inputElement} of elements) {
      const oldOffset = $modalElement.offset();
      const newOffset = $inputElement.offset();

      const $helper = $inputElement
        .clone()
        .appendTo($(bod))
        .width($inputElement.width());

      $inputElement.css('visibility', 'hidden');

      $helper.css({
        position: 'absolute',
        zIndex: 10000,
        top: oldOffset.top,
        left: oldOffset.left,
      });

      animations.push([$helper, {top: newOffset.top, left: newOffset.left}]);
    }

    await Craft.animateAll(animations);

    for (const [$helper] of animations) {
      $helper.remove();
    }

    for (const {$inputElement} of elements) {
      $inputElement.css('visibility', '');
    }
  }

  updateDisabledElementsInModal(): void {
    if (this.modal?.elementIndex) {
      this.modal.elementIndex.disableElementsById(this.getDisabledElementIds());
    }
  }

  getElementById(id: number): any {
    for (let i = 0; i < this.$elements.length; i++) {
      const $element = this.$elements.eq(i);
      if ($element.data('id') == id) {
        return $element;
      }
    }
  }

  onSelectElements(elements: any[]): void {
    this.trigger('selectElements', {elements});
    this.settings.onSelectElements(elements);
    this.$container.trigger('change');
  }

  onAddElements(): void {
    this.trigger('addElements');
    this.settings.onAddElements();
    this.$container.trigger('change');
  }

  onRemoveElements(): void {
    this.trigger('removeElements');
    this.settings.onRemoveElements();
    this.$container.trigger('change');
  }

  _animateStructureElementAway($allElements: any, i: number): void {
    let callback: (() => void) | undefined;

    if (i === $allElements.length - 1) {
      const $li = $allElements.first().parent().parent();
      const $ul = $li.parent();
      callback = () => {
        if ($ul[0] === this.$elementsContainer[0] || $li.siblings().length) {
          $li.remove();
        } else {
          $ul.remove();
        }
      };
    }

    const func = () => {
      this.animateElementAway($allElements.eq(i), callback);
    };

    if (i === 0) {
      func();
    } else {
      setTimeout(func, 100 * i);
    }
  }

  canCreateElements(): boolean {
    return false;
  }

  async createElement(title: string): Promise<number | null> {
    void title;
    return null;
  }

  initSearch(): void {
    if (!this.$searchInput.length) {
      return;
    }

    this.addListener(this.$searchInput[0], 'input', () => {
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      this.searchTimeout = setTimeout(() => {
        this.searchForTags();
      }, 500);
    });

    this.addListener(this.$searchInput[0], 'keydown', ((ev: KeyboardEvent) => {
      if (ev.keyCode === RETURN_KEY) {
        ev.preventDefault();
      }

      switch (ev.keyCode) {
        case RETURN_KEY: {
          ev.preventDefault();
          if (this.searchMenu) {
            this.selectSearchResult(this.searchMenu.$options.filter('.hover'));
          }
          return;
        }
        case DOWN_KEY: {
          ev.preventDefault();
          if (this.searchMenu) {
            const $hoverOption = this.searchMenu.$options.filter('.hover');
            if ($hoverOption.length) {
              const $nextOption = $hoverOption
                .parent()
                .nextAll()
                .find('.menu-item:not(.disabled)')
                .first();
              if ($nextOption.length) {
                this.focusOption($nextOption);
              }
            } else {
              this.focusOption(this.searchMenu.$options.eq(0));
            }
          }
          return;
        }
        case UP_KEY: {
          ev.preventDefault();
          if (this.searchMenu) {
            const $hoverOption = this.searchMenu.$options.filter('.hover');
            if ($hoverOption.length) {
              const $prevOption = $hoverOption
                .parent()
                .prevAll()
                .find('.menu-item:not(.disabled)')
                .last();
              if ($prevOption.length) {
                this.focusOption($prevOption);
              }
            } else {
              this.focusOption(
                this.searchMenu.$options.eq(this.searchMenu.$options.length - 1)
              );
            }
          }
          return;
        }
      }
    }) as any);

    this.addListener(this.$searchInput[0], 'focus', () => {
      if (this.searchMenu) {
        this.searchMenu.show();
      }
    });

    this.addListener(this.$searchInput[0], 'blur', () => {
      if (this._ignoreSearchBlur) {
        this._ignoreSearchBlur = false;
        return;
      }
      setTimeout(() => {
        if (this.searchMenu) {
          this.searchMenu.hide();
        }
      }, 1);
    });
  }

  async searchForTags(): Promise<void> {
    if (this.searchMenu) {
      this.killSearchMenu();
    }

    const val = this.$searchInput.val();

    if (val) {
      this.$searchSpinner.removeClass('hidden');
      Craft.cp.announce(Craft.t('app', 'Loading'));

      const excludeIds: number[] = [];
      for (let i = 0; i < this.$elements.length; i++) {
        const id = $(this.$elements[i]).data('id');
        if (id) excludeIds.push(id);
      }

      if (this.settings.sourceElementId && !this.settings.allowSelfRelations) {
        excludeIds.push(this.settings.sourceElementId);
      }

      let response: any;

      try {
        response = await Craft.sendActionRequest(
          'POST',
          'element-search/search',
          {
            data: {
              elementType: this.settings.elementType,
              siteId: this.settings.criteria.siteId,
              search: val,
              criteria: this.settings.searchCriteria,
              condition: this.settings.condition,
              referenceElementId: this.settings.referenceElementId
                ? this.elementEditor?.getDraftElementId(
                    this.settings.referenceElementId
                  ) || this.settings.referenceElementId
                : null,
              referenceElementOwnerId: this.settings.referenceElementOwnerId
                ? this.elementEditor?.getDraftElementId(
                    this.settings.referenceElementOwnerId
                  ) || this.settings.referenceElementOwnerId
                : null,
              referenceElementSiteId: this.settings.referenceElementSiteId,
              excludeIds,
            },
          }
        );
      } finally {
        if (this.searchMenu) {
          this.killSearchMenu();
        }
        this.$searchSpinner.addClass('hidden');
        Craft.cp.announce(Craft.t('app', 'Loading complete'));
      }

      const $menu = $('<div class="menu tagmenu"/>').appendTo($(bod));
      const $ul = $('<ul/>').appendTo($menu);

      for (const element of response.data.elements) {
        const $li = $('<li/>').appendTo($ul);
        const optionLabel = `${Craft.t('app', 'Existing {type}', {
          type:
            Craft.elementTypeNames[this.settings.elementType]?.[2] ??
            Craft.t('app', 'element'),
        })}: ${element.title}`;
        $li.attr('aria-label', optionLabel);

        const $item = $('<div class="menu-item"/>')
          .appendTo($li)
          .html(element.html)
          .data('id', element.id);

        if (element.exclude) {
          $item.addClass('disabled');
        }
      }

      if (!response.data.exactMatch && this.canCreateElements()) {
        const $li = $('<li/>').appendTo($ul);
        const optionLabel = `${Craft.t('app', 'Create {type}', {
          type:
            Craft.elementTypeNames[this.settings.elementType]?.[2] ??
            Craft.t('app', 'element'),
        })}: ${val}`;
        $li.attr('aria-label', optionLabel);

        $('<div class="menu-item" data-icon="plus"/>').appendTo($li).text(val);
      }

      $ul.find('.menu-item:not(.disabled):first').addClass('hover');

      this.searchMenu = new CustomSelect($menu[0], {
        anchor: this.$searchInput[0],
        onOptionSelect: (option: Element) => {
          this.selectSearchResult(option);
        },
      });

      this.$searchInput.attr('aria-controls', this.searchMenu.menuId);

      this.searchMenu.on('show', () => {
        this.$searchInput.attr('aria-expanded', 'true');
        this.focusSelectedOption();
        Craft.cp.elementThumbLoader.load($menu);
      });

      this.searchMenu.on('hide', () => {
        this.$searchInput.attr('aria-expanded', 'false');
        this.$searchInput.removeAttr('aria-activedescendant');
      });

      // mousedown on menu → keep focus on search input (ignore next blur).
      this.addListener($menu[0], 'mousedown', () => {
        this._ignoreSearchBlur = true;
      });

      this.searchMenu.show();
    } else {
      this.$searchSpinner.addClass('hidden');
    }
  }

  focusOption($option: any): void {
    this.searchMenu.$options.removeClass('hover');
    this.searchMenu.$ariaOptions.attr('aria-selected', 'false');
    $option.addClass('hover');
    this.$searchInput.attr(
      'aria-activedescendant',
      $option.parent('li').attr('id')
    );
  }

  focusSelectedOption(): void {
    const $option = this.searchMenu.$options.filter('.hover:first');
    if ($option.length) {
      this.focusOption($option);
    } else {
      this.focusFirstOption();
    }
  }

  focusFirstOption(): void {
    const $option = this.searchMenu.$options.first();
    this.focusOption($option);
  }

  async selectSearchResult(option: any): Promise<void> {
    const $option = $(option);

    if ($option.hasClass('disabled')) {
      return;
    }

    let id = $option.data('id');

    if (!id && this.canCreateElements()) {
      id = await this.createElement($option.text());
    }

    if (id) {
      await this.onModalSelect([
        {
          id,
          siteId: this.settings.criteria.siteId,
        },
      ]);
    }

    this.killSearchMenu();
    this.$searchInput.val('');
    setTimeout(() => {
      this.focusNextLogicalElement();
    }, 200);
  }

  killSearchMenu(): void {
    this.searchMenu.hide();
    this.searchMenu.destroy();
    this.searchMenu = null;
  }
}
