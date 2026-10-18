import {
  Base,
  deferUntil,
  ESC_KEY,
  hasAttr,
  RETURN_KEY,
} from '@craftcms/garnish';
// Listbox was migrated out of `@craftcms/garnish` into the app.
import {Listbox} from '@/modules/listbox/listbox';
import {Tab} from './tab';
import {CardViewDesigner} from './card-view-designer';
import {ElementDrag, TabDrag} from './drags';
import {
  fldElementData,
  fldTabData,
  htmlToElement,
  hudData,
  isSidebarTab,
  type FieldLayoutHud,
} from './support';
import type {FieldLayoutConfig, FieldLayoutDesignerSettings} from './types';
import {ButtonVariant, t} from '@craftcms/ui';
import {openSlideout} from '@/common/slideouts';

// `Craft` and jQuery (`$`) are still globals on the page. FLD is native; `$` is
// used ONLY at the Craft-interop seams (Craft.ui/Grid/listbox/Slideout return or
// require jQuery). Everywhere else FLD uses native DOM.
declare const Craft: any;
declare const $: any;

/**
 * Field Layout Designer — a port of the legacy `Craft.FieldLayoutDesigner` onto
 * the modern, jQuery-free `@craftcms/garnish` named API. FLD's own DOM work is
 * native (`document.*`, `classList`, `dataset`, WeakMaps); jQuery survives only
 * at the boundary with Craft's still-jQuery widgets.
 */
export class FieldLayoutDesigner extends Base<FieldLayoutDesignerSettings> {
  static defaults: FieldLayoutDesignerSettings = {
    elementType: null,
    customizableTabs: true,
    customizableUi: true,
    withCardViewDesigner: false,
    alwaysShowThumbAlignmentBtns: false,
    readOnly: false,
  };

  $container: any = null;
  $innerContainer: any = null;
  $configInput: any = null;
  $tabContainer: any = null;
  /** Holds the content tabs, which wrap across the workspace. */
  $contentTabContainer: any = null;
  /** Holds the sidebar tabs, in a column along the workspace's inline end. */
  $sidebarTabContainer: any = null;
  $newTabBtn: any = null;
  $newSidebarTabBtn: any = null;
  $libraryContainer: any = null;
  $selectedLibrary: any = null;
  $fieldLibrary: any = null;
  $uiLibrary: any = null;
  $uiLibraryElements: any = null;
  $fieldSearch: any = null;
  $clearFieldSearchBtn: any = null;
  $fieldGroups: HTMLElement[] = [];
  $fields: any = null;
  $createFieldBtn: any = null;

  libraryPicker: Listbox | null = null;
  tabGrid: any = null;
  /** Keeps the workspace a whole number of grid cells tall. */
  workspaceHeightObserver: ResizeObserver | null = null;
  sidebarTabHeightObserver: ResizeObserver | null = null;
  elementDrag: ElementDrag | null = null;
  tabDrag: TabDrag | null = null;

  cvd: CardViewDesigner | null = null;
  $cvd: any = null;

  _config: FieldLayoutConfig | null = null;
  _$selectedFields: any = null;
  constructor(
    container: HTMLElement | string,
    settings?: Partial<FieldLayoutDesignerSettings>
  ) {
    super();
    this.$container =
      container instanceof HTMLElement
        ? container
        : document.querySelector(container);
    if (!this.$container) {
      throw new Error('FieldLayoutDesigner: container not found.');
    }
    this.setSettings(settings, FieldLayoutDesigner.defaults);

    this.$configInput = this.$container.querySelector(
      ':scope > input[data-config-input]'
    );
    this._config = JSON.parse(this.$configInput.value);
    if (!this._config!.tabs) {
      this._config!.tabs = [];
    }

    this.$innerContainer = this.$container.querySelector(
      ':scope > .fld-container'
    );
    const $workspace = this.$innerContainer.querySelector(
      ':scope > .fld-workspace'
    );
    this.$tabContainer = $workspace.querySelector(':scope > .fld-tabs');
    this.$contentTabContainer =
      this.$tabContainer.querySelector('.fld-tabs__content');
    this.$sidebarTabContainer =
      this.$tabContainer.querySelector('.fld-tabs__sidebar');
    this.$newTabBtn = $workspace.querySelector('[command="--add-tab"]');
    this.$newSidebarTabBtn = $workspace.querySelector(
      '[command="--add-sidebar-tab"]'
    );
    this.$libraryContainer = this.$innerContainer.querySelector(
      ':scope > .fld-library'
    );

    this.$fieldLibrary = this.$selectedLibrary =
      this.$libraryContainer.querySelector(':scope > .fld-field-library');
    this.$fieldGroups = Array.from(
      this.$libraryContainer.querySelectorAll('.fld-field-group')
    );
    this.$fields = this.collectLibraryFields();
    this.$uiLibrary = this.$libraryContainer.querySelector(
      ':scope > .fld-ui-library'
    );
    this.$uiLibraryElements = Array.from(this.$uiLibrary.children);

    if (this.settings!.readOnly) {
      this.$fieldLibrary.setAttribute('tabindex', '-1');
    }

    // `typeof` rather than a bare `$`: until the legacy bundle loads there is no
    // global to reference at all, and an undeclared identifier throws a
    // ReferenceError instead of evaluating falsy — which `deferUntil` would
    // surface as a rejected promise that never polls again.
    void deferUntil(
      () =>
        !!Craft?.Grid &&
        typeof $ === 'function' &&
        !!this.$fieldLibrary.querySelector('craft-input-search input')
    ).then(() => {
      this.$fieldSearch = this.$fieldLibrary.querySelector(
        'craft-input-search input'
      );
      this.deferredInit();
    });
  }

  deferredInit(): void {
    // Set up the layout grids — Craft.Grid is a jQuery seam.
    // Tabs are 18 cells of the workspace's background grid wide, and each row
    // of them is snapped to a whole number of cells tall, so later rows and
    // the New Tab button land on the grid too.
    const gridSize =
      parseFloat(
        getComputedStyle(this.$contentTabContainer.parentElement).backgroundSize
      ) || 16;
    // Only content tabs wrap into rows; sidebar tabs stack in their column.
    this.tabGrid = new Craft.Grid($(this.$contentTabContainer), {
      itemSelector: '.fld-tab',
      minColWidth: gridSize * 18,
      fillMode: 'grid',
      snapToGrid: gridSize,
    });

    this.snapWorkspaceHeight(gridSize);
    this.snapSidebarTabHeights(gridSize);

    for (const tab of this.tabElements()) {
      this.initTab(tab);
    }

    this.elementDrag = new ElementDrag(this);
    if (!this.settings!.readOnly) {
      this.initLibraryElements(
        this.$libraryContainer.querySelectorAll('.fld-element')
      );
    }

    if (this.settings!.customizableTabs) {
      this.tabDrag = new TabDrag(this);

      this.addListener(this.$newTabBtn, 'activate', () => {
        this.addTab('content');
      });
      this.addListener(this.$newSidebarTabBtn, 'activate', () => {
        this.addTab('sidebar');
      });
    }

    // Set up the library
    if (this.settings!.customizableUi) {
      const $libraryPicker =
        this.$libraryContainer.querySelector(':scope > .btngroup');
      if ($libraryPicker) {
        this.libraryPicker = new Listbox($libraryPicker, {
          onChange: (option) => {
            const library =
              option instanceof HTMLElement
                ? option.dataset.library
                : undefined;
            switch (library) {
              case 'field':
                this.$fieldLibrary.classList.remove('hidden');
                this.$uiLibrary.classList.add('hidden');
                this.$createFieldBtn.classList.remove('hidden');
                break;
              case 'ui':
                this.$fieldLibrary.classList.add('hidden');
                this.$uiLibrary.classList.remove('hidden');
                this.$createFieldBtn.classList.add('hidden');
                break;
            }
          },
        });
      }
    }

    this.addListener(this.$fieldSearch, 'input', () =>
      this.updateFieldSearchResults()
    );

    this.addListener(this.$fieldSearch, 'keydown', (ev: any) => {
      switch (ev.keyCode) {
        case ESC_KEY:
          this.clearSearch();
          break;
        case RETURN_KEY:
          // they most likely don't want to submit the form from here
          ev.preventDefault();
          break;
      }
    });

    // Clear the search when the X button is clicked
    this.addListener(this.$clearFieldSearchBtn, 'click', () => {
      this.clearSearch();
    });

    this.refreshSelectedFields();

    const libraryFooter = document.createElement('div');
    libraryFooter.classList.add('pt-4');

    // Add the “New Field” button — Craft.ui returns jQuery; unwrap to native.
    const createFieldBtn = document.createElement('craft-button');
    createFieldBtn.variant = ButtonVariant.Dashed;
    createFieldBtn.size = 'small';
    createFieldBtn.classList.add('w-full');
    createFieldBtn.innerText = t('New field');
    createFieldBtn.disabled = this.settings!.readOnly;

    this.$createFieldBtn = createFieldBtn;
    libraryFooter.appendChild(this.$createFieldBtn);
    this.$libraryContainer.appendChild(libraryFooter);

    this.addListener(this.$createFieldBtn, 'activate', async () => {
      this.createField();
    });

    // initiate Card Attributes Designer
    if (this.settings!.withCardViewDesigner) {
      this.$cvd = this.$container
        .closest('.fld-cvd')
        ?.querySelector('.card-view-designer');
      this.initCvd();
    }

    // "Field settings" announces a save as `field-saved` from its menu item,
    // which sits in a settings panel outside the designer.
    this.addListener(document, 'field-saved', (ev) => {
      const selectorHtml =
        ev instanceof CustomEvent ? ev.detail?.selectorHtml : null;
      if (selectorHtml) {
        this.refreshField(String(selectorHtml));
      }
    });
  }

  /** Swaps in a saved field's library selector and refreshes its instances. */
  refreshField(selectorHtml: string): void {
    const $newSelector = htmlToElement(selectorHtml);
    const fieldId = $newSelector.dataset.id;
    const $oldSelector = this.$fieldLibrary.querySelector(
      `.fld-field[data-id="${fieldId}"]`
    );

    if (!$oldSelector) {
      return;
    }

    $oldSelector.replaceWith($newSelector);
    this.refreshLibraryFields();
    this.elementDrag!.removeItems($oldSelector);
    this.elementDrag!.addItems($newSelector);

    this.$tabContainer
      .querySelectorAll(`.fld-field[data-id="${fieldId}"]`)
      .forEach((el: HTMLElement) => fldElementData.get(el)?.refresh());
  }

  /** Direct `.fld-element` children of every field group, in DOM order. */
  collectLibraryFields(): HTMLElement[] {
    const fields: HTMLElement[] = [];
    for (const group of this.$fieldGroups) {
      fields.push(
        ...Array.from(
          group.querySelectorAll<HTMLElement>(':scope > .fld-element')
        )
      );
    }
    return fields;
  }

  updateFieldSearchResults(): void {
    const val = this.$fieldSearch?.value.toLowerCase().replace(/['"]/g, '');
    if (!val) {
      this.$fieldLibrary
        .querySelectorAll('.filtered')
        .forEach((el: HTMLElement) => el.classList.remove('filtered'));
      return;
    }

    const matches = new Set<HTMLElement>();
    for (const el of this.$fields) {
      if (el.matches(`[data-keywords*="${val}"]`)) {
        matches.add(el);
      }
    }
    for (const group of this.$fieldGroups) {
      if (group.matches(`[data-name*="${val}"]`)) {
        for (const el of group.querySelectorAll(':scope > .fld-element')) {
          if (el instanceof HTMLElement) {
            matches.add(el);
          }
        }
      }
    }
    for (const el of matches) {
      el.classList.remove('filtered');
    }
    for (const el of this.$fields) {
      if (!matches.has(el)) {
        el.classList.add('filtered');
      }
    }

    // hide any groups that don't have any results
    for (const group of this.$fieldGroups) {
      if (group.querySelector('.fld-element:not(.hidden):not(.filtered)')) {
        group.classList.remove('filtered');
      } else {
        group.classList.add('filtered');
      }
    }
  }

  clearSearch(): void {
    this.$fieldSearch.value = '';
    this.$fieldSearch.dispatchEvent(new Event('input'));
  }

  initCvd(): void {
    this.cvd = new CardViewDesigner(this, this.$cvd);

    // Add skip link
    const skipLinkAnchor = this.cvd.$container.id;

    if (skipLinkAnchor) {
      const $skipLink = document.createElement('a');
      $skipLink.className = 'skip-link';
      $skipLink.textContent = Craft.t('app', 'Skip to card view designer');
      $skipLink.href = `#${skipLinkAnchor}`;

      this.cvd.$container.setAttribute('tabindex', '-1');
      this.$innerContainer.prepend($skipLink);
    }
  }

  initTab($tab: any): Tab {
    const content = isSidebarTab($tab)
      ? $tab.querySelector(':scope > .fld-tabcontent')
      : null;
    if (content) {
      this.sidebarTabHeightObserver?.observe(content);
    }

    return new Tab(this, $tab);
  }

  /**
   * Tear the designer down so it can be re-booted when Inertia replaces the host's
   * innerHTML after a save. Cascades to the CVD and disposes resources that outlive
   * the detached FLD subtree: drag controllers, library picker, Craft.Grid (binds a
   * window `resize`), and every tab's HUD (lives in `<body>`). Detached-DOM
   * listeners and their WeakMaps are released by `super.destroy()` / GC.
   */
  override destroy(): void {
    this.workspaceHeightObserver?.disconnect();
    this.workspaceHeightObserver = null;
    this.sidebarTabHeightObserver?.disconnect();
    this.sidebarTabHeightObserver = null;
    this.cvd?.destroy();
    this.cvd = null;

    this.elementDrag?.destroy();
    this.elementDrag = null;
    this.tabDrag?.destroy();
    this.tabDrag = null;
    this.libraryPicker?.destroy();
    this.libraryPicker = null;

    // Craft.Grid binds a window `resize` listener — dispose it explicitly.
    this.tabGrid?.destroy?.();
    this.tabGrid = null;

    // Each tab's HUD lives in <body>, so it won't be GC'd with the detached
    // subtree — dispose every tab.
    if (this.$tabContainer) {
      this.tabElements().forEach((tabEl) => fldTabData.get(tabEl)?.dispose());
    }

    super.destroy();
  }

  removeFieldByHandle(attribute: string): void {
    const field = this.$fields.find((el: HTMLElement) =>
      el.matches(`[data-attribute="${attribute}"]`)
    );
    if (field) {
      field.classList.remove('hidden');
      field.closest('.fld-field-group')?.classList.remove('hidden');
    }
  }

  addTab(type: 'content' | 'sidebar' = 'content'): void {
    if (!this.settings!.customizableTabs) {
      return;
    }

    let defaultValue = '';
    if (this.tabElements().length === 0) {
      defaultValue = Craft.t('app', 'Content');
    }
    const name = Craft.escapeHtml(
      prompt(Craft.t('app', 'Give your tab a name.'), defaultValue)
    );

    if (!name) {
      return;
    }

    const $tab = htmlToElement(`
<div class="fld-tab${type === 'sidebar' ? ' fld-tab--sidebar' : ''}">
  <div class="tabs">
    <div class="tab sel draggable">
      <h3 class="fld-tab__name">${name}</h3>
    </div>
  </div>
  <div class="fld-tabcontent">
    <craft-button
      type="button"
      class="w-full fld-add-btn mt-2"
      command="--add-field"
      variant="outline"
      size="small"
    >
      <craft-icon name="plus" slot="prefix"></craft-icon>
      ${t('Add')}
    </craft-button>
  </div>
</div>
`);
    if (type === 'sidebar') {
      this.$sidebarTabContainer.appendChild($tab);
    } else {
      // Only content tabs wrap into the grid's rows.
      this.$contentTabContainer.appendChild($tab);
      this.tabGrid.addItems($($tab));
    }
    this.tabDrag!.addItems($tab);

    const tab = this.initTab($tab);
    tab.updatePositionInConfig();
  }

  /**
   * Keeps the workspace (and so its panes, which stretch to fill it) a whole
   * number of grid cells tall, so its bottom edge lands on the grid too. Its
   * height comes from its content, which CSS can't round, so whenever either
   * column of tabs resizes, this measures the workspace's natural height and
   * rounds its minimum height up to the next grid cell.
   */
  snapWorkspaceHeight(gridSize: number): void {
    const $workspace: HTMLElement = this.$tabContainer.parentElement;

    const snap = () => {
      $workspace.style.removeProperty('min-block-size');
      const height = $workspace.getBoundingClientRect().height;
      // A sliver of tolerance, so fractional pixels don't add a whole cell.
      const cells = Math.ceil(height / gridSize - 0.01);
      $workspace.style.minBlockSize = `${cells * gridSize}px`;
    };

    this.workspaceHeightObserver = new ResizeObserver(snap);
    this.workspaceHeightObserver.observe(this.$contentTabContainer);
    this.workspaceHeightObserver.observe(this.$sidebarTabContainer);
  }

  /**
   * Sidebar tabs stack in their column rather than wrapping into the grid's
   * rows, so the grid doesn't snap them. Each is snapped to a whole number of
   * grid cells tall here instead, as the grid does for content tabs' rows, so
   * the next tab and the New Tab button below them land on the grid too.
   * `initTab()` observes each sidebar tab's content, which stays its natural
   * height however tall the tab is.
   */
  snapSidebarTabHeights(gridSize: number): void {
    this.sidebarTabHeightObserver = new ResizeObserver((entries) => {
      for (const {target} of entries) {
        const tab = target.parentElement;
        if (!tab?.isConnected) {
          continue;
        }

        tab.style.removeProperty('block-size');
        const height = tab.getBoundingClientRect().height;
        // A sliver of tolerance, so fractional pixels don't add a whole cell.
        const cells = Math.ceil(height / gridSize - 0.01);
        tab.style.blockSize = `${cells * gridSize}px`;
      }
    });
  }

  /** Every tab, in layout order: the content tabs, then the sidebar tabs. */
  tabElements(): HTMLElement[] {
    return Array.from(
      this.$tabContainer.querySelectorAll(
        '.fld-tabs__content > .fld-tab, .fld-tabs__sidebar > .fld-tab'
      )
    );
  }

  get config(): FieldLayoutConfig {
    return this._config!;
  }

  set config(config: FieldLayoutConfig) {
    this._config = config;
    this.$configInput.value = JSON.stringify(config);

    // Assigning `.value` fires nothing, and the designer's edits are drags and
    // menu actions, so wrappers have no other signal that the config changed.
    this.$configInput.dispatchEvent(new Event('change', {bubbles: true}));
  }

  updateConfig(
    callback: (config: FieldLayoutConfig) => FieldLayoutConfig | false
  ): void {
    const config = callback(this.config);
    if (config !== false) {
      this.config = config;
    }
  }

  refreshSelectedFields(): void {
    this._$selectedFields = Array.from(
      this.$tabContainer.querySelectorAll('.fld-field')
    );
  }

  refreshLibraryFields(): void {
    this.$fields = this.collectLibraryFields();

    for (const $fieldGroup of this.$fieldGroups) {
      const $fields = Array.from(
        $fieldGroup.querySelectorAll<HTMLElement>(':scope > .fld-element')
      );
      $fields.sort((a, b) =>
        (a.dataset.uiLabel ?? '') > (b.dataset.uiLabel ?? '') ? 1 : -1
      );
      for (const el of $fields) {
        $fieldGroup.appendChild(el);
      }
    }

    this.updateFieldSearchResults();
  }

  hasHandle(handle: string): boolean {
    for (const el of this._$selectedFields) {
      const element = fldElementData.get(el);
      if (!element) {
        continue;
      }
      const elementHandle = element.config.handle || element.attribute;
      if (handle === elementHandle) {
        return true;
      }
    }

    return false;
  }

  createField(): void {
    // The library lives in a HUD that `HUD.show()` re-parents to <body>, so
    // `$createFieldBtn` sits outside the panel DOM. Opening from it would read
    // as coming from the base page and close the panel we're inside; the HUD's
    // trigger is the button in the tab, which stays put. It's also the right
    // place to send focus back to, since the HUD closes below.
    const hud = this.getActiveHud();

    void openSlideout(Craft.getCpUrl('settings/fields/edit'), {
      opener: hud?.$trigger ?? this.$createFieldBtn,
      onSaved: ({data}) => {
        // add the library selector
        const selectorHtml = data?.selectorHtml;
        if (Object(selectorHtml).constructor !== String) {
          throw new Error('The saved field response omitted selector HTML.');
        }
        const $selector = htmlToElement(String(selectorHtml));
        const $lastGroup = this.$fieldGroups[this.$fieldGroups.length - 1];
        if (!$lastGroup) {
          throw new Error('The field library requires at least one group.');
        }
        $lastGroup.appendChild($selector);
        $lastGroup.classList.remove('hidden');
        this.refreshLibraryFields();
        this.initLibraryElements($selector);

        // add it to the active tab
        this.addLibraryElementToActiveTab($selector);
      },
    });

    // Close the library popup now the editor is open — the HUD only hides, so
    // `getActiveHud()` still resolves it for the save handler above.
    hud?.hide();
  }

  initLibraryElements($elements: any): void {
    this.elementDrag!.addItems($elements);

    this.addListener($elements, 'activate', (ev: any) => {
      // ignore if is on a dragged element
      if (ev.currentTarget.style.visibility === 'hidden') {
        return;
      }
      ev.stopPropagation();
      ev.originalEvent.preventDefault();
      this.addLibraryElementToActiveTab(ev.currentTarget);
    });
  }

  cloneLibraryElementForSelection(libraryElement: HTMLElement): HTMLElement {
    // Create a new element based on that one
    const $libraryElement = libraryElement;
    const $element = $libraryElement.cloneNode(true);
    if (!($element instanceof HTMLElement)) {
      throw new Error('Expected a field layout element clone.');
    }
    $element.classList.remove('unused');
    $element.removeAttribute('tabindex');

    if (!hasAttr($libraryElement, 'data-is-multi-instance')) {
      // Hide the library element
      $libraryElement.style.visibility = 'inherit';
      $libraryElement.style.display = 'field';
      $libraryElement.classList.add('hidden');

      // Hide the group too?
      const visibleSibling = Array.from(
        $libraryElement.parentElement?.children ?? []
      ).some(
        (el) => el !== $libraryElement && el.matches('.fld-field:not(.hidden)')
      );
      if (!visibleSibling) {
        $libraryElement.closest('.fld-field-group')?.classList.add('hidden');
      }
    }

    // Add it to the element dragger
    this.elementDrag!.addItems($element);

    return $element;
  }

  getActiveHud(): FieldLayoutHud | undefined {
    const hudEl = this.$libraryContainer.closest('.fld-library-hud');
    return hudEl ? hudData.get(hudEl) : undefined;
  }

  addLibraryElementToActiveTab(libraryElement: HTMLElement): void {
    const hud = this.getActiveHud();
    if (!hud) {
      return;
    }

    const $element = this.cloneLibraryElementForSelection(libraryElement);
    const tabContainer = hud.$trigger.closest('.fld-tab');
    if (!tabContainer) {
      throw new Error('Field layout HUD is not inside a tab.');
    }
    const tab = fldTabData.get(tabContainer);
    hud.$trigger.before($element);
    const element = tab!.initElement($element);
    element.onSelect();
    element.updatePositionInConfig();
  }
}
