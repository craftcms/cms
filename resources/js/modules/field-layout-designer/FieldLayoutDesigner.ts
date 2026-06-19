import {
  Base,
  ESC_KEY,
  RETURN_KEY,
  hasAttr,
  requestAnimationFrame,
} from '@craftcms/garnish';
import {Tab} from './Tab';
import {CardViewDesigner} from './CardViewDesigner';
import {ElementDrag, TabDrag} from './drags';
import {fldTabData, fldElementData, hudData, htmlToElement} from './support';
import type {FieldLayoutDesignerSettings, FieldLayoutConfig} from './types';

// `Craft` and jQuery (`$`) are still globals on the page. FLD is native; `$` is
// used ONLY at the Craft-interop seams (Craft.ui/Grid/Listbox/Slideout return or
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
  $newTabBtn: any = null;
  $libraryContainer: any = null;
  $selectedLibrary: any = null;
  $fieldLibrary: any = null;
  $uiLibrary: any = null;
  $uiLibraryElements: any = null;
  $fieldSearch: any = null;
  $clearFieldSearchBtn: any = null;
  $fieldGroups: any = null;
  $fields: any = null;
  $createFieldBtn: any = null;

  libraryPicker: any = null;
  tabGrid: any = null;
  elementDrag: ElementDrag | null = null;
  tabDrag: TabDrag | null = null;

  cvd: CardViewDesigner | null = null;
  $cvd: any = null;

  _config: FieldLayoutConfig | null = null;
  _$selectedFields: any = null;
  _fieldHandles: Record<string, unknown> = {};

  constructor(container: any, settings?: Partial<FieldLayoutDesignerSettings>) {
    super();
    this.$container =
      typeof container === 'string'
        ? document.querySelector(container)
        : container;
    this.setSettings(settings, FieldLayoutDesigner.defaults);

    this.$configInput = this.$container.querySelector(
      ':scope > input[data-config-input]'
    );
    this._config = JSON.parse(this.$configInput.value);
    if (!this._config!.tabs) {
      this._config!.tabs = [];
    }

    this._fieldHandles = {};

    this.$innerContainer = this.$container.querySelector(
      ':scope > .fld-container'
    );
    const $workspace = this.$innerContainer.querySelector(
      ':scope > .fld-workspace'
    );
    this.$tabContainer = $workspace.querySelector(':scope > .fld-tabs');
    this.$newTabBtn = $workspace.querySelector(':scope > .fld-new-tab-btn');
    this.$libraryContainer = this.$innerContainer.querySelector(
      ':scope > .fld-library'
    );

    this.$fieldLibrary = this.$selectedLibrary =
      this.$libraryContainer.querySelector(':scope > .fld-field-library');
    const $fieldSearchContainer =
      this.$fieldLibrary.querySelector(':scope > .search');
    this.$fieldSearch = $fieldSearchContainer.querySelector(':scope > input');
    this.$clearFieldSearchBtn = $fieldSearchContainer.querySelector(
      ':scope > .clear-btn'
    );
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
    // Set up the layout grids — Craft.Grid is a jQuery seam.
    this.tabGrid = new Craft.Grid($(this.$tabContainer), {
      itemSelector: '.fld-tab',
      minColWidth: 24 * 13,
      fillMode: 'grid',
      snapToGrid: 24,
    });

    const $tabs = Array.from(this.$tabContainer.children);
    for (const tab of $tabs) {
      this.initTab(tab as HTMLElement);
    }

    this.elementDrag = new ElementDrag(this);
    if (!this.settings!.readOnly) {
      this.initLibraryElements(
        this.$libraryContainer.querySelectorAll('.fld-element')
      );
    }

    if (this.settings!.customizableTabs) {
      this.tabDrag = new TabDrag(this);

      this.addListener(this.$newTabBtn, 'activate', 'addTab');
    }

    // Set up the library
    if (this.settings!.customizableUi) {
      const $libraryPicker =
        this.$libraryContainer.querySelector(':scope > .btngroup');
      // Craft.Listbox is a jQuery seam; its onChange hands us a jQuery option.
      this.libraryPicker = new Craft.Listbox($($libraryPicker), {
        onChange: ($selectedOption: any) => {
          const library = $selectedOption[0]?.dataset.library;
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

    this.addListener(this.$fieldSearch, 'input', () => {
      this.updateFieldSearchResults();
    });

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

    // Add the “New Field” button — Craft.ui returns jQuery; unwrap to native.
    this.$createFieldBtn = Craft.ui.createButton({
      label: Craft.t('app', 'New field'),
      class: 'mt-m fullwidth add icon dashed',
      disabled: this.settings!.readOnly,
    })[0];
    this.$libraryContainer.appendChild(this.$createFieldBtn);

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
  }

  /** Direct `.fld-element` children of every field group, in DOM order. */
  collectLibraryFields(): HTMLElement[] {
    const fields: HTMLElement[] = [];
    for (const group of this.$fieldGroups) {
      fields.push(
        ...(Array.from(
          group.querySelectorAll(':scope > .fld-element')
        ) as HTMLElement[])
      );
    }
    return fields;
  }

  updateFieldSearchResults(): void {
    const val = this.$fieldSearch.value.toLowerCase().replace(/['"]/g, '');
    if (!val) {
      this.$fieldLibrary
        .querySelectorAll('.filtered')
        .forEach((el: HTMLElement) => el.classList.remove('filtered'));
      this.$clearFieldSearchBtn.classList.add('hidden');
      return;
    }

    this.$clearFieldSearchBtn.classList.remove('hidden');

    const matches = new Set<HTMLElement>();
    for (const el of this.$fields) {
      if (el.matches(`[data-keywords*="${val}"]`)) {
        matches.add(el);
      }
    }
    for (const group of this.$fieldGroups) {
      if (group.matches(`[data-name*="${val}"]`)) {
        for (const el of group.querySelectorAll(':scope > .fld-element')) {
          matches.add(el as HTMLElement);
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
      $skipLink.className = 'skip-link btn';
      $skipLink.textContent = Craft.t('app', 'Skip to card view designer');
      $skipLink.href = `#${skipLinkAnchor}`;

      this.cvd.$container.setAttribute('tabindex', '-1');
      this.$innerContainer.prepend($skipLink);
    }
  }

  initTab($tab: any): Tab {
    return new Tab(this, $tab);
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

  addTab(): void {
    if (!this.settings!.customizableTabs) {
      return;
    }

    let defaultValue = '';
    if (this.tabGrid.$items.length === 0) {
      defaultValue = Craft.t('app', 'Content');
    }
    const name = Craft.escapeHtml(
      prompt(Craft.t('app', 'Give your tab a name.'), defaultValue)
    );

    if (!name) {
      return;
    }

    const $tab = htmlToElement(`
<div class="fld-tab">
  <div class="tabs">
    <div class="tab sel draggable">
      <h3 class="fld-tab__name">${name}</h3>
    </div>
  </div>
  <div class="fld-tabcontent">
    <button class="btn add icon dashed fullwidth fld-add-btn" type="button">
      ${Craft.t('app', 'Add')}
    </button>
  </div>
</div>
`);
    // keep it before the resize object
    const $tabs = this.$tabContainer.querySelectorAll(':scope > .fld-tab');
    const $lastTab = $tabs[$tabs.length - 1];
    if ($lastTab) {
      $lastTab.after($tab);
    } else {
      this.$tabContainer.prepend($tab);
    }

    this.tabGrid.addItems($($tab));
    this.tabDrag!.addItems($tab);

    const tab = this.initTab($tab);
    tab.updatePositionInConfig();
  }

  get config(): FieldLayoutConfig {
    return this._config!;
  }

  set config(config: FieldLayoutConfig) {
    this._config = config;
    this.$configInput.value = JSON.stringify(config);
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
        $fieldGroup.querySelectorAll(':scope > .fld-element')
      ) as HTMLElement[];
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
    const slideout = new Craft.CpScreenSlideout('fields/edit-field');

    slideout.on('submit', async ({response}: any) => {
      // add the library selector
      const $selector = htmlToElement(response.data.selectorHtml);
      const $lastGroup = this.$fieldGroups[this.$fieldGroups.length - 1];
      $lastGroup.appendChild($selector);
      $lastGroup.classList.remove('hidden');
      this.refreshLibraryFields();
      this.initLibraryElements($selector);

      // add it to the active tab
      this.addLibraryElementToActiveTab($selector);

      requestAnimationFrame(() => {
        this.getActiveHud()?.hide();
      });
    });
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

  cloneLibraryElementForSelection(libraryElement: any): HTMLElement {
    // Create a new element based on that one
    const $libraryElement = libraryElement as HTMLElement;
    const $element = $libraryElement.cloneNode(true) as HTMLElement;
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

  getActiveHud(): any {
    const hudEl = this.$libraryContainer.closest('.fld-library-hud');
    return hudEl ? hudData.get(hudEl) : undefined;
  }

  addLibraryElementToActiveTab(libraryElement: any): void {
    const hud = this.getActiveHud();
    if (!hud) {
      return;
    }

    const $element = this.cloneLibraryElementForSelection(libraryElement);
    const tab = fldTabData.get(hud.$trigger.closest('.fld-tab'));
    hud.$trigger.before($element);
    const element = tab!.initElement($element);
    element.onSelect();
    element.updatePositionInConfig();
  }

  static async createSlideout(
    data: any,
    js: string | null,
    settings: any = {}
  ): Promise<any> {
    const $body = document.createElement('div');
    $body.className = 'fld-element-settings-body';
    const $fields = document.createElement('div');
    $fields.className = 'fields';
    $fields.innerHTML = data.settingsHtml;
    $body.appendChild($fields);

    const $footer = document.createElement('div');
    $footer.className = 'fld-element-settings-footer';
    const $flexGrow = document.createElement('div');
    $flexGrow.className = 'flex-grow';
    $footer.appendChild($flexGrow);

    // Craft.ui returns jQuery — unwrap to native at the seam.
    const $cancelBtn = Craft.ui.createButton({
      label: Craft.t('app', 'Close'),
      spinner: true,
    })[0];
    $footer.appendChild($cancelBtn);
    $footer.appendChild(
      Craft.ui.createSubmitButton({
        class: 'secondary',
        label: Craft.t('app', 'Apply'),
        spinner: true,
      })[0]
    );

    // Craft.Slideout is a jQuery widget — pass a jQuery wrapper at the seam.
    const slideout = new Craft.Slideout(
      $([$body, $footer]),
      Object.assign(
        {
          containerElement: 'form',
          containerAttributes: {
            action: '',
            method: 'post',
            novalidate: '',
            class: 'fld-element-settings',
          },
        },
        settings
      )
    );
    slideout.on('open', () => {
      // Hold off a sec until it's positioned...
      requestAnimationFrame(() => {
        // Focus on the first text input
        (
          slideout.$container[0].querySelector('.text') as HTMLElement | null
        )?.focus();
      });
    });

    $cancelBtn.addEventListener('click', () => {
      slideout.close();
    });

    if (data.headHtml) {
      await Craft.appendHeadHtml(data.headHtml);
    }
    if (data.bodyHtml) {
      await Craft.appendBodyHtml(data.bodyHtml);
    }
    if (js) {
      eval(js);
    }

    Craft.initUiElements(slideout.$container);

    return slideout;
  }
}
