import {
  BACKSPACE_KEY,
  Base,
  CustomSelect,
  DELETE_KEY,
  DOWN_KEY,
  DragSort,
  firstFocusableElement,
  getOffset,
  hasAttr,
  isFocusable,
  RETURN_KEY,
  Select,
  UP_KEY,
  type GarnishBaseSettings,
} from '@craftcms/garnish';
import {
  animate,
  animateAll,
  getElementInfo,
  setElementSize,
  type ElementInfo,
} from '@/common/craft/element-markup';
import {announce} from '@/common/announcer';
import {loadElementThumbs} from '@/modules/element-thumb-loader/globals';
import {iconSvg} from '@/common/craft/icon-svg';
import {elementTypeName} from '@/common/craft/element-types';
import {isRtl} from '@/common/craft/direction';
import {elementSelectInputData} from './support';
import {getActionUrl} from '@/common/craft/url';
import {t} from '@craftcms/ui/utilities/translate';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {appendBodyHtml, appendHeadHtml} from '@craftcms/ui/utilities/dom';
import {addActionsToChip, type ChipAction} from '@/common/craft/chip-actions';
import {hasMousePointerEvents} from '@/common/craft/pointer';
import {showError} from '@/modules/messages/messages';
import {jq} from '@/common/utils/jquery';
import {onLongPress} from '@vueuse/core';

declare const Craft: any;

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

interface ElementSelectModalSettings {
  matchSiteBeforeDisablingElement?: boolean;
  siteId?: number;
}

type ElementSelectViewMode =
  | 'list'
  | 'list-inline'
  | 'thumbs'
  | 'large'
  | 'cards'
  | 'cards-grid';

/** A selected element, as the modal and search hand it over. */
export interface SelectedElement {
  id: number;
  siteId: number | null;
  /** The chip to add, once rendered. */
  element?: HTMLElement | null;
  [key: string]: unknown;
}

export interface BaseElementSelectInputSettings extends GarnishBaseSettings {
  id: string | null;
  name: string | null;
  fieldId: number | null;
  elementType: string | null;
  sources: string[] | null;
  condition: ElementCriteria | null;
  referenceElementId: number | null;
  referenceElementOwnerId: number | null;
  referenceElementSiteId: number | null;
  criteria: ElementCriteria;
  searchCriteria: ElementCriteria | null;
  allowAdd: boolean;
  allowRemove: boolean;
  allowSelfRelations: boolean;
  sourceElementId: number | null;
  disabledElementIds: number[] | null;
  viewMode: ElementSelectViewMode;
  single: boolean;
  maintainHierarchy: boolean;
  branchLimit: number | null;
  limit: number | null;
  defaultPlacement: 'beginning' | 'end';
  showSiteMenu: boolean;
  siteIds: number[] | null;
  modalStorageKey: string | null;
  modalSettings: ElementSelectModalSettings;
  onAddElements(): void;
  onSelectElements(elements: SelectedElement[]): void;
  onRemoveElements(): void;
  sortable: boolean;
  selectable: boolean;
  showActionMenu: boolean;
  editable: boolean;
  prevalidate: boolean;
  editorSettings: Record<string, never>;
  canUpload?: boolean;
  describedBy?: string;
  sectionId?: number;
  targetSiteId?: number;
  selectionLabel?: string;
}

interface ActionMenuElement extends HTMLElement {
  opened: boolean;
}

const DEFAULTS: BaseElementSelectInputSettings = {
  id: null,
  name: null,
  fieldId: null,
  elementType: null,
  sources: null,
  condition: null,
  referenceElementId: null,
  referenceElementOwnerId: null,
  referenceElementSiteId: null,
  criteria: {},
  searchCriteria: null,
  allowAdd: true,
  allowRemove: true,
  allowSelfRelations: false,
  sourceElementId: null,
  disabledElementIds: null,
  viewMode: 'list',
  single: false,
  maintainHierarchy: false,
  branchLimit: null,
  limit: null,
  defaultPlacement: 'end',
  showSiteMenu: false,
  siteIds: null,
  modalStorageKey: null,
  modalSettings: {},
  onAddElements: noop,
  onSelectElements: noop,
  onRemoveElements: noop,
  sortable: true,
  selectable: true,
  // Whether chips get an action menu at all: it's forwarded to
  // `app/render-elements` for the chips fetched after a selection, and gates
  // the client-side `addActionsToChip()` injection in `addElements()`.
  // Set it to `false` when the host renders the chips' menus itself (e.g.
  // `ElementSelectControl.vue`).
  showActionMenu: true,
  editable: true,
  prevalidate: false,
  editorSettings: {},
};

const CHIP_ACTIONS =
  ':scope > .chip-content > .chip-actions, :scope > .card-titlebar > .card-actions-container > .card-actions';

/**
 * A relation field's element list: drag-sort, selection, search-as-you-type,
 * and the element selector modal. Booted by `<craft-element-select-input>`
 * and its entry and asset variants.
 */
export class BaseElementSelectInput extends Base<BaseElementSelectInputSettings> {
  declare settings: BaseElementSelectInputSettings;

  static readonly ADD_FX_DURATION = 200;
  static readonly REMOVE_FX_DURATION = 200;
  static defaults = DEFAULTS;

  elementSelect: Select | null = null;
  elementSort: DragSort | null = null;
  modal: any = null;
  elementEditor: any = null;
  modalFirstOpen = true;
  openingModal = false;

  container!: HTMLElement;
  form: HTMLFormElement | null = null;
  elementsContainer!: HTMLElement;
  elements: HTMLElement[] = [];
  addElementBtn: HTMLElement | null = null;
  spinner: HTMLElement | null = null;

  searchTimeout: ReturnType<typeof setTimeout> | null = null;
  searchMenu: CustomSelect | null = null;
  searchContainer: HTMLElement | null = null;
  searchInput: HTMLInputElement | null = null;
  searchSpinner: HTMLElement | null = null;

  modalStorageKey: string | null = null;

  replacing: HTMLElement | null = null;
  #ignoreSearchBlur = false;
  #cleanups: Array<() => void> = [];

  constructor(settings?: Partial<BaseElementSelectInputSettings>) {
    super();
    if (new.target === BaseElementSelectInput) {
      this.init(settings);
    }
  }

  init(settings?: Partial<BaseElementSelectInputSettings>): void {
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

    const container = this.getContainer();
    if (!container) {
      throw new Error(`No element select input with ID [${this.settings.id}].`);
    }
    this.container = container;
    elementSelectInputData.set(this.container, this);
    this.form = this.container.closest('form');

    this.elementsContainer = this.getElementsContainer();
    this.addElementBtn = this.getAddElementsBtn();
    this.spinner = this.getSpinner();
    this.searchContainer = this.getSearchContainer();
    this.searchInput = this.getSearchInput();
    this.searchSpinner = this.getSearchSpinner();

    this.initElementSelect();
    this.initElementSort();
    this.initReorderButtons();
    this.initSearch();
    this.resetElements();

    if (this.addElementBtn) {
      // `<craft-button>` fires click for Enter and Space too
      this.addListener(
        this.addElementBtn,
        'click',
        () => void this.showModal()
      );
    }

    if (this.elementSelect) {
      this.addListener(window, 'mousedown', (ev) => {
        const target = ev.target;
        if (
          target instanceof Element &&
          !this.container.contains(target) &&
          !target.closest('.menu')
        ) {
          this.elementSelect?.deselectAll();
        }
      });
    }

    // The legacy element editor stores itself on its form
    setTimeout(() => {
      this.elementEditor = this.form
        ? (jq()?.(this.form).data('elementEditor') ?? null)
        : null;
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

    for (const cleanup of this.#cleanups) {
      cleanup();
    }
    this.#cleanups = [];

    this.elementSort?.destroy();
    this.elementSort = null;
    this.elementSelect?.destroy();
    this.elementSelect = null;
    this.modal?.destroy?.();
    this.modal = null;

    elementSelectInputData.delete(this.container);

    super.destroy();
  }

  get totalSelected(): number {
    return this.elements.length;
  }

  getContainer(): HTMLElement | null {
    return this.settings.id ? document.getElementById(this.settings.id) : null;
  }

  getElementsContainer(): HTMLElement {
    return this.container.querySelector<HTMLElement>(':scope > .elements')!;
  }

  getElements(): HTMLElement[] {
    const selector = this.elementsContainer.classList.contains('structure')
      ? ':scope > li .row .element'
      : ':scope > li > .element';

    return [...this.elementsContainer.querySelectorAll<HTMLElement>(selector)];
  }

  getSearchContainer(): HTMLElement | null {
    return this.container.querySelector<HTMLElement>(
      '.elementselect__search-input-wrapper'
    );
  }

  getSearchInput(): HTMLInputElement | null {
    return this.searchContainer?.querySelector('input') ?? null;
  }

  getSearchSpinner(): HTMLElement | null {
    return this.searchContainer?.querySelector<HTMLElement>('.spinner') ?? null;
  }

  getAddElementsBtn(): HTMLElement | null {
    return this.container.querySelector<HTMLElement>(
      '[command="--add-element"], .btn.add'
    );
  }

  getSpinner(): HTMLElement | null {
    return this.container.querySelector<HTMLElement>('.spinner');
  }

  initElementSelect(): void {
    if (this.settings.selectable) {
      this.elementSelect = new Select(this.elementSelectSettings());
    }
  }

  elementSelectSettings(): Record<string, unknown> {
    return {
      multi: this.settings.sortable,
      filter: (target: Element) =>
        !target.closest('a[href],button,[role=button]'),
      makeFocusable: false,
    };
  }

  /**
   * Moves elements when their `<craft-reorder-button>`'s Move up/down menu
   * items are chosen, whether the server or `createNewElement()` added it.
   */
  initReorderButtons(): void {
    this.addListener(this.elementsContainer, 'craft-reorder', (ev) => {
      const button =
        ev.target instanceof Element
          ? ev.target.closest('craft-reorder-button')
          : null;
      const element = button?.closest<HTMLElement>('.element');
      if (!element) {
        return;
      }

      const {direction} = (
        ev as unknown as CustomEvent<{direction: 'up' | 'down'}>
      ).detail;
      if (direction === 'up') {
        this.moveElementForward(element);
      } else {
        this.moveElementBackward(element);
      }
    });
  }

  initElementSort(): void {
    if (!this.settings.sortable || !hasMousePointerEvents()) {
      return;
    }

    this.elementSort = new DragSort({
      container: this.elementsContainer,
      filter: this.settings.selectable
        ? () => {
            const target = this.elementSort?.$targetItem;
            const element = target?.querySelector(':scope > .element');
            if (element?.classList.contains('sel') && this.elementSelect) {
              return this.elementSelect
                .getSelectedItems()
                .map((item) => item.parentElement!)
                .filter((li) => li.tagName === 'LI');
            }
            return target ? [target] : [];
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
      axis: this.getElementSortAxis(),
      collapseDraggees: true,
      magnetStrength: 4,
      helperLagBase: 1.5,
      onBeforeDragStart: () => {
        this.elementEditor?.pause();
        // Keep the labels' overflow tooltips from popping up mid-drag
        for (const truncate of this.elementsContainer.querySelectorAll(
          'craft-truncate'
        )) {
          truncate.setAttribute('disabled', '');
        }
      },
      onDragStop: () => {
        this.elementEditor?.resume();
        for (const truncate of this.elementsContainer.querySelectorAll(
          'craft-truncate'
        )) {
          truncate.removeAttribute('disabled');
        }
      },
      onSortChange: () => {
        this.onSortChange();
      },
    } as any);
  }

  getElementSortAxis(): 'x' | 'y' | null {
    if (
      this.settings.viewMode === 'list' &&
      !this.elementsContainer.classList.contains('inline-chips')
    ) {
      return 'y';
    }
    return null;
  }

  canAddMoreElements(): boolean {
    return (
      this.settings.allowAdd &&
      (!this.settings.limit || this.elements.length < this.settings.limit)
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
    if (this.settings.allowAdd && this.addElementBtn) {
      this.addElementBtn.classList.remove('hidden');
      this.searchContainer?.classList.remove('hidden');
    }
    this.updateButtonContainer();
  }

  disableAddElementsBtn(): void {
    if (this.addElementBtn) {
      this.addElementBtn.classList.add('hidden');
      this.searchContainer?.classList.add('hidden');
    }
    this.updateButtonContainer();
  }

  showSpinner(): void {
    this.spinner?.classList.remove('hidden');
    this.updateButtonContainer();
  }

  hideSpinner(): void {
    this.spinner?.classList.add('hidden');
    this.updateButtonContainer();
  }

  updateButtonContainer(): void {
    const container = this.addElementBtn?.parentElement;
    if (!container?.classList.contains('flex')) {
      return;
    }

    const anyVisible = [...container.children].some(
      (child) => !child.classList.contains('hidden')
    );
    container.classList.toggle('hidden', !anyVisible);
  }

  getNextLogicalFocusElement(): HTMLElement | null {
    if (!this.canAddMoreElements()) {
      return this.getLastActionBtn();
    }

    if (
      this.searchInput &&
      !this.searchContainer?.classList.contains('hidden')
    ) {
      return this.searchInput;
    }

    if (
      this.addElementBtn &&
      !this.addElementBtn.classList.contains('hidden')
    ) {
      return this.addElementBtn;
    }

    this.container.setAttribute('tabindex', '-1');
    return this.container;
  }

  focusNextLogicalElement(): void {
    this.getNextLogicalFocusElement()?.focus();
  }

  getLastActionBtn(): HTMLElement | null {
    const buttons = this.container.querySelectorAll<HTMLElement>('.action-btn');
    return buttons[buttons.length - 1] ?? null;
  }

  focusLastActionBtn(): void {
    this.getLastActionBtn()?.focus();
  }

  resetElements(): void {
    if (this.elements.length) {
      this.removeElements(this.elements);
    }
    this.addElements(this.getElements());
  }

  addElements(elements: HTMLElement[]): void {
    for (const element of elements) {
      const actions = this.settings.showActionMenu
        ? this.defineElementActions(element)
        : [];

      if (actions.length) {
        addActionsToChip(element, actions);
        this.#watchMoveActions(element);
      }

      if (this.settings.sortable) {
        // Its menu reorders without a mouse, so it's shown on touch devices too.
        const reorderButton = document.createElement('craft-reorder-button');
        reorderButton.classList.add('move-btn');
        if (this.getElementSortAxis() !== 'y') {
          reorderButton.setAttribute('orientation', 'horizontal');
        }
        element.querySelector(CHIP_ACTIONS)?.append(reorderButton);
      }

      if (this.settings.editable) {
        this.addListener(element, 'dblclick', () => this.#editElement(element));
        this.#cleanups.push(
          onLongPress(
            element,
            (ev) => {
              if (
                ev.pointerType === 'touch' &&
                !(ev.target instanceof Element && ev.target.closest('button'))
              ) {
                this.#editElement(element);
              }
            },
            {delay: 750}
          )
        );
      }

      this.addListener(element, 'keydown', (ev) => {
        if (
          ev instanceof KeyboardEvent &&
          [BACKSPACE_KEY, DELETE_KEY].includes(ev.keyCode)
        ) {
          ev.stopPropagation();
          ev.preventDefault();
          for (const selected of this.elementSelect?.getSelectedItems() ?? []) {
            this.removeElement(selected);
          }
        }
      });
    }

    loadElementThumbs(elements);

    // `?.` because a host that re-renders this input mid-selection can destroy
    // the controller between the modal resolving and the chips landing.
    if (this.settings.selectable) {
      this.elementSelect?.addItems(elements);
    }

    if (this.settings.sortable) {
      this.elementSort?.addItems(
        elements.map((element) => element.parentElement!)
      );
    }

    this.elements = [...this.elements, ...elements];
    this.updateAddElementsBtn();
    this.onAddElements();
    this.onSortChange();
  }

  /** Hides the Move up/down menu items that don't apply when the menu opens. */
  #watchMoveActions(element: HTMLElement): void {
    const actionMenu =
      element.querySelector<ActionMenuElement>('craft-action-menu');
    if (!actionMenu) {
      return;
    }

    const moveForwardItem = actionMenu.querySelector('[data-move-forward]');
    const moveBackwardItem = actionMenu.querySelector('[data-move-backward]');

    // No public "opening" event, so watch the reflected `opened` attribute
    const observer = new MutationObserver(() => {
      if (!actionMenu.opened) {
        return;
      }

      const li = element.parentElement;
      moveForwardItem?.toggleAttribute('hidden', !li?.previousElementSibling);
      moveBackwardItem?.toggleAttribute('hidden', !li?.nextElementSibling);
    });
    observer.observe(actionMenu, {
      attributes: true,
      attributeFilter: ['opened'],
    });
    this.#cleanups.push(() => observer.disconnect());
  }

  #editElement(element: HTMLElement): void {
    if (
      hasAttr(element, 'data-editable') &&
      !element.classList.contains('disabled') &&
      !element.classList.contains('loading')
    ) {
      this.createElementEditor(element);
    }
  }

  defineElementActions(element: HTMLElement): ChipAction[] {
    // `iconSvg()` is typed to resolve to null too, which ChipAction doesn't allow
    const actions: any[] = [];

    if (this.settings.sortable) {
      const axis = this.getElementSortAxis();
      actions.push({
        icon: async () =>
          await iconSvg(
            axis === 'y' ? 'arrow-up' : isRtl() ? 'arrow-right' : 'arrow-left'
          ),
        label: axis === 'y' ? t('Move up') : t('Move forward'),
        callback: () => this.moveElementForward(element),
        attributes: {'data-move-forward': true},
      });
      actions.push({
        icon: async () =>
          await iconSvg(
            axis === 'y' ? 'arrow-down' : isRtl() ? 'arrow-left' : 'arrow-right'
          ),
        label: axis === 'y' ? t('Move down') : t('Move backward'),
        callback: () => this.moveElementBackward(element),
        attributes: {'data-move-backward': true},
      });
    }

    if (this.settings.allowRemove) {
      if (this.settings.elementType) {
        actions.push({
          icon: async () => await iconSvg('arrows-rotate'),
          label: t('Replace'),
          callback: () => this.showReplaceModal(element),
        });
      }

      actions.push({
        icon: async () => await iconSvg('remove'),
        label: t('Remove'),
        callback: () => this.removeElementOrSelection(element),
      });
    }

    return actions;
  }

  /**
   * Opens the element selector modal with `element` staged as the chip the
   * next selection replaces. Shared with hosts that render the chips' action
   * menus themselves, via `<craft-element-select-input>.replaceElement()`.
   */
  showReplaceModal(element: HTMLElement): void {
    this.replacing = element;
    void this.showModal();
  }

  /**
   * Removes `element`, or the whole selection when it's part of it, as the
   * Remove action does.
   */
  removeElementOrSelection(element: HTMLElement): void {
    if (this.elementSelect?.isSelected(element)) {
      this.removeElement(this.elementSelect.getSelectedItems());
    } else {
      this.removeElement(element);
    }
  }

  /** The chip for an element ID. */
  getElementById(id: number | string): HTMLElement | undefined {
    return this.elements.find((element) => element.dataset.id == String(id));
  }

  /** Opens an element's editor slideout. */
  createElementEditor(
    element: HTMLElement,
    settings?: Record<string, unknown>
  ): void {
    const params = Object.fromEntries(
      Object.entries({
        elementType: this.settings.elementType,
        elementId: element.dataset.id,
        draftId: element.dataset.draftId,
        siteId: element.dataset.siteId,
        prevalidate: this.settings.prevalidate ? 1 : null,
        ...settings,
      }).filter(([, value]) => value !== null && value !== undefined)
    );

    void Craft.openSlideout(getActionUrl('elements/edit', params), {
      opener: element,
      onSaved: ({data}: {data?: Record<string, any>}) => {
        // An image saved as a new asset takes the edited asset's place.
        if (data?.replacedAssetId && data.newAssetId) {
          void this.replaceElement(data.replacedAssetId, data.newAssetId);
        }
      },
    });
  }

  async replaceElement(
    elementId: number,
    replacementId: number
  ): Promise<void> {
    if (!this.settings.allowRemove) {
      throw new Error('Elements cannot be removed from this input.');
    }

    const existing = this.getElementById(elementId);
    if (!existing) {
      throw new Error(`No element selected with an ID of ${elementId}.`);
    }

    this.showSpinner();

    try {
      const {data} = await actionClient.post('app/render-elements', {
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
      });

      this.removeElement(existing);
      await this.selectElements([
        this.#selectedElement(getElementInfo(data.elements[replacementId][0])),
      ]);
      await appendHeadHtml(data.headHtml);
      await appendBodyHtml(data.bodyHtml);
    } catch (e: any) {
      showError(e?.response?.data?.message);
      throw e;
    } finally {
      this.hideSpinner();
    }
  }

  #selectedElement(info: ElementInfo): SelectedElement {
    return {...info, id: Number(info.id), siteId: info.siteId};
  }

  onSortChange(): void {
    this.elementSelect?.resetItemOrder();
    // Keep `elements` in DOM order
    this.elements.sort((a, b) =>
      a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1
    );
  }

  moveElementForward(element: HTMLElement): void {
    const li = element.closest('li');
    const prev = li?.previousElementSibling;
    if (li && prev) {
      prev.before(li);
      this.onSortChange();
    }
  }

  moveElementBackward(element: HTMLElement): void {
    const li = element.closest('li');
    const next = li?.nextElementSibling;
    if (li && next) {
      next.after(li);
      this.onSortChange();
    }
  }

  /**
   * Removes elements from the field value without removing their DOM nodes.
   */
  removeElements(elements: HTMLElement | HTMLElement[]): void {
    const removing = Array.isArray(elements) ? elements : [elements];

    if (this.settings.selectable) {
      this.elementSelect?.removeItems(removing);
    }

    for (const element of removing) {
      for (const input of element.querySelectorAll<HTMLInputElement>(
        ':scope > input'
      )) {
        input.disabled = true;
      }
    }

    let focusTarget: HTMLElement | null = null;
    if (this.settings.selectable && removing.length) {
      const lastIdx = this.elements.indexOf(removing[removing.length - 1]!);
      const neighbor =
        this.elements[lastIdx + 1] ??
        (lastIdx - 1 >= 0 ? this.elements[lastIdx - 1] : undefined);
      if (neighbor && !removing.includes(neighbor)) {
        focusTarget = this.getElementFocusTarget(neighbor);
      }
    }

    this.elements = this.elements.filter((el) => !removing.includes(el));

    // Removing a relation makes it selectable again
    this.updateDisabledElementsInModal();

    if (focusTarget) {
      focusTarget.focus();
    } else {
      setTimeout(() => this.focusNextLogicalElement(), 200);
    }

    this.updateAddElementsBtn();
    this.onSortChange();
    this.onRemoveElements();
  }

  getElementFocusTarget(element: HTMLElement): HTMLElement | null {
    return isFocusable(element) ? element : firstFocusableElement(element);
  }

  /**
   * Completely removes element(s) from the UI and field value.
   */
  removeElement(elements: HTMLElement | HTMLElement[]): void {
    let removing = Array.isArray(elements) ? elements : [elements];

    if (this.settings.maintainHierarchy) {
      const descendants = removing.flatMap((element) =>
        [...(element.parentElement?.parentElement?.children ?? [])]
          .filter((sibling) => sibling.tagName === 'UL')
          .flatMap((ul) => [...ul.querySelectorAll<HTMLElement>('.element')])
      );
      removing = [...new Set([...removing, ...descendants])];
    }

    for (const element of removing) {
      for (const named of element.querySelectorAll('[name]')) {
        named.removeAttribute('name');
      }
    }
    this.removeElements(removing);

    if (this.settings.maintainHierarchy) {
      this.#animateStructureElementsAway(removing);
    } else {
      for (const element of removing) {
        const li =
          element.parentElement?.tagName === 'LI'
            ? element.parentElement
            : null;
        void this.animateElementAway(element);
        li?.remove();
      }
    }
  }

  async animateElementAway(
    element: HTMLElement,
    callback?: () => void
  ): Promise<void> {
    const offset = getOffset(element);
    const width = element.getBoundingClientRect().width;

    document.body.append(element);
    Object.assign(element.style, {
      zIndex: '0',
      position: 'absolute',
      top: `${offset.top}px`,
      left: `${offset.left}px`,
      maxWidth: `${width}px`,
    });

    await animate(element, {
      opacity: -1,
      left: offset.left + 100 * (isRtl() ? 1 : -1),
    });

    element.remove();
    callback?.();
  }

  /** Animates structure chips away one after another, then tidies up their list items. */
  #animateStructureElementsAway(elements: HTMLElement[]): void {
    const li = elements[0]?.parentElement?.parentElement;
    const ul = li?.parentElement;

    elements.forEach((element, i) => {
      const callback =
        i === elements.length - 1 && li && ul
          ? () => {
              if (
                ul === this.elementsContainer ||
                li.parentElement?.children.length! > 1
              ) {
                li.remove();
              } else {
                ul.remove();
              }
            }
          : undefined;

      setTimeout(
        () => void this.animateElementAway(element, callback),
        100 * i
      );
    });
  }

  async showModal(): Promise<void> {
    if (!this.replacing && !this.canAddMoreElements()) {
      return;
    }

    if (this.modal) {
      void this.modal.show();
      return;
    }

    // Guards the await below: without it a double click opens two modals.
    if (this.openingModal) {
      return;
    }

    this.openingModal = true;

    try {
      this.modal = await this.createModal();
      this.modalFirstOpen = false;
    } finally {
      this.openingModal = false;
    }
  }

  /**
   * The modal stack — Lit, Vue and the element index — is imported here rather
   * than at module scope, so a page carrying a relation field doesn't load it
   * unless a modal is actually opened.
   */
  async createModal(): Promise<any> {
    const {elementType} = this.settings;

    if (!elementType) {
      throw new Error(
        'An element select input needs an element type to open its selector.'
      );
    }

    const {createElementSelectorModal} =
      await import('@/modules/element-selector-modal/create-element-selector-modal');

    return createElementSelectorModal(elementType, this.getModalSettings());
  }

  getModalSettings(): Record<string, any> {
    const settings: Record<string, any> = Object.assign(
      {
        closeOtherModals: false,
        storageKey: this.modalStorageKey,
        sources: this.settings.sources,
        condition: this.settings.condition,
        referenceElementId: this.#draftId(this.settings.referenceElementId),
        referenceElementOwnerId: this.#draftId(
          this.settings.referenceElementOwnerId
        ),
        referenceElementSiteId: this.settings.referenceElementSiteId,
        criteria: Object.assign({}, this.settings.criteria),
        multiSelect: this.settings.limit != 1,
        hideOnSelect: false,
        showSiteMenu: this.settings.showSiteMenu,
        siteIds: this.settings.siteIds,
        disabledElementIds: this.getDisabledElementIds(),
        onSelect: this.onModalSelect.bind(this),
        // Called whenever the modal closes: Cancel, Escape or a selection
        onClose: this.onModalHide.bind(this),
        triggerElement: () => this.getNextLogicalFocusElement(),
        modalTitle: t('Choose'),
      },
      this.settings.modalSettings
    );

    if (!this.modalFirstOpen) {
      settings.preferStoredSource = true;
    }

    return settings;
  }

  /** An element ID, or its draft's when the legacy element editor has one. */
  #draftId(id: number | null): number | null {
    return id ? this.elementEditor?.getDraftElementId(id) || id : null;
  }

  getSelectedElementIds(): number[] {
    const {matchSiteBeforeDisablingElement, siteId} =
      this.settings.modalSettings;

    return this.elements
      .filter(
        (element) =>
          !(matchSiteBeforeDisablingElement && siteId) ||
          element.dataset.siteId == String(siteId)
      )
      .map((element) => Number(element.dataset.id));
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

  async onModalSelect(elements: SelectedElement[]): Promise<void> {
    this.elementEditor?.pause();

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

    const {data} = await actionClient.post('app/render-elements', {
      elements: [
        {
          type: this.settings.elementType,
          id: elements.map((e) => e.id),
          siteId: elements[0]?.siteId,
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
    });

    for (const element of elements) {
      const html = data.elements[element.id]?.[0];
      if (html !== undefined) {
        element.element = getElementInfo(html).element;
      }
    }

    // Drop the chip being replaced only now that the replacement markup is in
    // hand, and never before an await. Hosts that re-render this input from
    // its value (`ElementSelectControl.vue` keys the custom element on it)
    // would otherwise see an intermediate value and tear the element down
    // mid-flight. It still lands ahead of the limit math below, which needs the
    // outgoing chip gone to free up its slot.
    if (this.replacing) {
      this.removeElement(this.replacing);
      this.replacing = null;
    }

    if (this.settings.maintainHierarchy) {
      await this.selectStructuredElements(elements);
    } else {
      if (this.settings.limit) {
        const slotsLeft = this.settings.limit - this.elements.length;
        if (elements.length > slotsLeft) {
          elements = elements.slice(0, slotsLeft);
        }
      }

      await this.selectElements(elements);
      this.updateDisabledElementsInModal();
    }

    this.modal?.hide();

    await appendHeadHtml(data.headHtml);
    await appendBodyHtml(data.bodyHtml);

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
    this.replacing = null;
  }

  async selectElements(elements: SelectedElement[]): Promise<void> {
    const added: HTMLElement[] = [];

    for (const info of elements) {
      if (!info.element) {
        continue;
      }
      const element = this.createNewElement(info);
      this.appendElement(element);
      this.addElements([element]);
      info.element = element;
      added.push(element);
    }

    await this.animateElementsIn(added);
    this.onSelectElements(elements);
  }

  async selectStructuredElements(elements: SelectedElement[]): Promise<void> {
    const selectedElementIds =
      this.settings.branchLimit == 1 ? [] : this.getSelectedElementIds();

    selectedElementIds.push(...elements.map((el) => el.id));

    const {data} = await actionClient.post(
      'relational-fields/structured-input-html',
      {
        elementIds: selectedElementIds,
        siteId: elements[0]?.siteId,
        containerId: this.settings.id,
        name: this.settings.name,
        branchLimit: this.settings.branchLimit,
        selectionLabel: this.settings.selectionLabel,
        elementType: this.settings.elementType,
      }
    );

    const template = document.createElement('template');
    template.innerHTML = data.html.trim();
    const newElementsContainer =
      template.content.firstElementChild?.querySelector<HTMLElement>(
        ':scope > .elements'
      );

    if (newElementsContainer) {
      this.elementsContainer.replaceWith(newElementsContainer);
      this.elementsContainer = newElementsContainer;
    }

    await appendHeadHtml(data.headHtml);
    await appendBodyHtml(data.bodyHtml);

    this.elements = [];
    this.resetElements();

    const selected = elements.filter((element) => {
      element.element = this.getElementById(element.id) ?? null;
      return !!element.element;
    });

    await this.animateElementsIn(selected.map((element) => element.element!));
    this.updateDisabledElementsInModal();
    this.onSelectElements(selected);
  }

  createNewElement(info: SelectedElement): HTMLElement {
    const element = info.element!.cloneNode(true) as HTMLElement;
    setElementSize(
      element,
      ['thumbs', 'large'].includes(this.settings.viewMode) ? 'large' : 'small'
    );
    element.classList.add('removable');

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = this.settings.name + (this.settings.single ? '' : '[]');
    input.value = String(info.id);
    element.append(input);

    return element;
  }

  appendElement(element: HTMLElement): void {
    const li = document.createElement('li');
    li.append(element);
    if (this.settings.defaultPlacement === 'beginning') {
      this.elementsContainer.prepend(li);
    } else {
      this.elementsContainer.append(li);
    }
  }

  /** Fades newly added chips in. */
  async animateElementsIn(elements: HTMLElement[]): Promise<void> {
    if (!elements.length) {
      return;
    }

    for (const element of elements) {
      element.style.opacity = '0';
    }

    await animateAll(elements.map((element) => [element, {opacity: 1}]));

    for (const element of elements) {
      element.style.removeProperty('opacity');
    }
  }

  /**
   * Republishes which elements the modal may not select, as a whole set.
   */
  updateDisabledElementsInModal(): void {
    this.modal?.setDisabledElementIds(this.getDisabledElementIds());
  }

  #change(): void {
    this.container.dispatchEvent(new Event('change', {bubbles: true}));
  }

  onSelectElements(elements: SelectedElement[]): void {
    this.trigger('selectElements', {elements});
    this.settings.onSelectElements(elements);
    this.#change();
  }

  onAddElements(): void {
    this.trigger('addElements');
    this.settings.onAddElements();
    this.#change();
  }

  onRemoveElements(): void {
    this.trigger('removeElements');
    this.settings.onRemoveElements();
    this.#change();
  }

  canCreateElements(): boolean {
    return false;
  }

  async createElement(_title: string): Promise<number | null> {
    return null;
  }

  initSearch(): void {
    const input = this.searchInput;
    if (!input) {
      return;
    }

    this.addListener(input, 'input', () => {
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      this.searchTimeout = setTimeout(() => void this.search(), 500);
    });

    this.addListener(input, 'keydown', (ev) => {
      if (!(ev instanceof KeyboardEvent)) {
        return;
      }

      switch (ev.keyCode) {
        case RETURN_KEY: {
          ev.preventDefault();
          const hovered = this.#hoveredOption();
          if (this.searchMenu && hovered) {
            void this.selectSearchResult(hovered);
          }
          return;
        }
        case DOWN_KEY:
        case UP_KEY: {
          ev.preventDefault();
          this.#moveHover(ev.keyCode === DOWN_KEY ? 1 : -1);
          return;
        }
      }
    });

    this.addListener(input, 'focus', () => {
      this.searchMenu?.show();
    });

    this.addListener(input, 'blur', () => {
      if (this.#ignoreSearchBlur) {
        this.#ignoreSearchBlur = false;
        return;
      }
      setTimeout(() => this.searchMenu?.hide(), 1);
    });
  }

  #enabledOptions(): HTMLElement[] {
    return (this.searchMenu?.$options ?? []).filter(
      (option) => !option.classList.contains('disabled')
    );
  }

  #hoveredOption(): HTMLElement | undefined {
    return this.searchMenu?.$options.find((option) =>
      option.classList.contains('hover')
    );
  }

  #moveHover(step: 1 | -1): void {
    const options = this.#enabledOptions();
    if (!options.length) {
      return;
    }

    const hovered = this.#hoveredOption();
    const index = hovered ? options.indexOf(hovered) : -1;
    const next =
      index === -1
        ? options[step === 1 ? 0 : options.length - 1]
        : options[index + step];

    if (next) {
      this.focusOption(next);
    }
  }

  async search(): Promise<void> {
    if (this.searchMenu) {
      this.killSearchMenu();
    }

    const val = this.searchInput?.value;

    if (!val) {
      this.searchSpinner?.classList.add('hidden');
      return;
    }

    this.searchSpinner?.classList.remove('hidden');
    announce(t('Loading'));

    const excludeIds = this.elements
      .map((element) => Number(element.dataset.id))
      .filter(Boolean);

    if (this.settings.sourceElementId && !this.settings.allowSelfRelations) {
      excludeIds.push(this.settings.sourceElementId);
    }

    let response: any;

    try {
      response = await actionClient.post('element-search/search', {
        elementType: this.settings.elementType,
        siteId: this.settings.criteria.siteId,
        search: val,
        criteria: this.settings.searchCriteria,
        condition: this.settings.condition,
        referenceElementId: this.#draftId(this.settings.referenceElementId),
        referenceElementOwnerId: this.#draftId(
          this.settings.referenceElementOwnerId
        ),
        referenceElementSiteId: this.settings.referenceElementSiteId,
        excludeIds,
      });
    } finally {
      if (this.searchMenu) {
        this.killSearchMenu();
      }
      this.searchSpinner?.classList.add('hidden');
      announce(t('Loading complete'));
    }

    const typeName =
      (this.settings.elementType
        ? elementTypeName(this.settings.elementType)
        : null) ?? t('element');

    const menu = document.createElement('div');
    menu.className = 'menu tagmenu';
    const ul = document.createElement('ul');
    menu.append(ul);
    document.body.append(menu);

    const addOption = (label: string): HTMLElement => {
      const li = document.createElement('li');
      li.setAttribute('aria-label', label);
      const item = document.createElement('div');
      item.className = 'menu-item';
      li.append(item);
      ul.append(li);
      return item;
    };

    for (const element of response.data.elements) {
      const item = addOption(
        `${t('Existing {type}', {type: typeName})}: ${element.title}`
      );
      item.innerHTML = element.html;
      item.dataset.id = String(element.id);
      if (element.exclude) {
        item.classList.add('disabled');
      }
    }

    if (!response.data.exactMatch && this.canCreateElements()) {
      const item = addOption(`${t('Create {type}', {type: typeName})}: ${val}`);
      const icon = document.createElement('craft-icon');
      icon.setAttribute('name', 'plus');
      item.append(icon, val);
    }

    ul.querySelector('.menu-item:not(.disabled)')?.classList.add('hover');

    const searchMenu = new CustomSelect(menu, {
      anchor: this.searchInput,
      onOptionSelect: (option: Element) => {
        void this.selectSearchResult(option as HTMLElement);
      },
    } as any);
    this.searchMenu = searchMenu;

    this.searchInput?.setAttribute('aria-controls', searchMenu.menuId);

    searchMenu.on('show', () => {
      this.searchInput?.setAttribute('aria-expanded', 'true');
      this.focusSelectedOption();
      loadElementThumbs(menu);
    });

    searchMenu.on('hide', () => {
      this.searchInput?.setAttribute('aria-expanded', 'false');
      this.searchInput?.removeAttribute('aria-activedescendant');
    });

    // Clicking the menu shouldn't count as leaving the search input
    this.addListener(menu, 'mousedown', () => {
      this.#ignoreSearchBlur = true;
    });

    searchMenu.show();
  }

  focusOption(option: HTMLElement): void {
    for (const o of this.searchMenu?.$options ?? []) {
      o.classList.remove('hover');
    }
    for (const o of this.searchMenu?.$ariaOptions ?? []) {
      o.setAttribute('aria-selected', 'false');
    }
    option.classList.add('hover');

    const id = option.closest('li')?.id;
    if (id) {
      this.searchInput?.setAttribute('aria-activedescendant', id);
    }
  }

  focusSelectedOption(): void {
    const option = this.#hoveredOption() ?? this.searchMenu?.$options[0];
    if (option) {
      this.focusOption(option);
    }
  }

  async selectSearchResult(option: HTMLElement): Promise<void> {
    if (option.classList.contains('disabled')) {
      return;
    }

    let id: number | null = option.dataset.id
      ? Number(option.dataset.id)
      : null;

    if (!id && this.canCreateElements()) {
      id = await this.createElement(option.textContent ?? '');
    }

    if (id) {
      await this.onModalSelect([
        {id, siteId: (this.settings.criteria.siteId as number) ?? null},
      ]);
    }

    this.killSearchMenu();
    if (this.searchInput) {
      this.searchInput.value = '';
    }
    setTimeout(() => this.focusNextLogicalElement(), 200);
  }

  killSearchMenu(): void {
    this.searchMenu?.hide();
    this.searchMenu?.destroy();
    this.searchMenu = null;
  }
}
