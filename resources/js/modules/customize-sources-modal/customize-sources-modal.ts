import {Modal, Base, DragSort, bod} from '@craftcms/garnish';

declare const Craft: any;
declare const Garnish: any;
declare const $: any;

// ─── CustomizeSourcesModal ────────────────────────────────────────────────────

/**
 * CustomizeSourcesModal — a port of `Craft.CustomizeSourcesModal` and all of
 * its nested companion classes onto modern TypeScript (extending
 * `@craftcms/garnish` base classes).
 *
 * Nested classes are exported individually and registered on
 * `Craft.CustomizeSourcesModal.*` via `registerCraftGlobals`. They are:
 *   - {@link PageSettingsModal}
 *   - {@link SourceDrag}
 *   - {@link Page}
 *   - {@link BaseSource}
 *   - {@link Source}
 *   - {@link CustomSource}
 *   - {@link Heading}
 *
 * Notes:
 * - `activate` events on jQuery-created buttons MUST be bound with jQuery
 *   `.on('activate', …)` — that is a jQuery synthetic event; `addListener`
 *   would use native `addEventListener` and miss it.
 * - `Garnish.DisclosureMenu` is accessed via `declare const Garnish: any`
 *   (the compat layer wires it; importing `DisclosureMenu` directly would
 *   bypass the compat init guard).
 * - `Craft.Listbox` is still a legacy class — accessed via `declare const Craft`.
 */
export class CustomizeSourcesModal extends Modal {
  declare settings: any;

  elementIndex: any = null;

  $body: any = null;
  $pagesSidebar: any = null;
  $pagesSidebarContent: any = null;
  $pagesSidebarItems: any = null;
  $newPageBtn: any = null;
  pageIconInputs: any = null;

  multiPage = false;
  pageDrag: any = null;
  pages: any[] | null = null;
  selectedPage: any = null;

  $sourcesSidebar: any = null;
  $sourcesSidebarContent: any = null;
  sourceContainers: Record<string, any> = {};
  $sourcesHeader: any = null;
  $newSourceBtn: any = null;

  $sourceSettingsOuterContainer: any = null;
  $sourceSettingsContainer: any = null;
  $sourceSettingsHeader: any = null;
  $sourceMenu: any = null;
  sourceMenu: any = null;
  $footer: any = null;
  $footerBtnContainer: any = null;
  $saveBtn: any = null;
  $cancelBtn: any = null;
  $loadingSpinner: any = null;

  sourceDrag: any = null;
  sources: any[] | null = null;
  selectedSource: any = null;
  destroying = false;

  elementTypeName: string | null = null;
  baseSortOptions: any[] | null = null;
  availableTableAttributes: any[] | null = null;
  customFieldAttributes: any[] | null = null;
  viewModes: any[] | null = null;

  conditionBuilderHtml: string | null = null;
  conditionBuilderJs: string | null = null;
  userGroups: any[] | null = null;

  #$container: any = null;

  constructor(elementIndex: any, settings?: any) {
    super(undefined as any, {autoShow: false});
    this.setSettings(settings, {resizable: true});

    this.elementIndex = elementIndex;

    this.#$container = $('<form class="modal cs-modal"/>').appendTo($(bod));
    this.$body = $('<div class="cs-body"/>').appendTo(this.#$container);

    const headerId = `cs-header-${Math.floor(Math.random() * 1000000)}`;
    this.$sourcesSidebar = $('<div/>', {
      class: 'cs-sidebar cs-selected-screen',
      role: 'navigation',
      'aria-labelledby': headerId,
    }).appendTo(this.$body);

    this.$sourcesHeader = $('<div class="cs-header"/>')
      .appendTo(this.$sourcesSidebar)
      .append(
        $('<h2/>', {id: headerId, class: 'h3'}).text(Craft.t('app', 'Sources'))
      );

    this.$sourcesSidebarContent = $(
      '<div class="cs-sidebar-content"/>'
    ).appendTo(this.$sourcesSidebar);
    this.sourceContainers = [];

    this.$sourceSettingsOuterContainer = $(
      '<div class="cs-source-settings--outer"/>'
    ).appendTo(this.$body);

    const $sourceSettingsHeader = $('<div class="cs-header"/>')
      .appendTo(this.$sourceSettingsOuterContainer)
      .append($('<h2 class="h3"/>').text(Craft.t('app', 'Settings')));

    const $backBtn = $('<button/>', {
      type: 'button',
      class: 'cs-back-btn',
      title: Craft.t('app', 'Back to sources'),
      'aria-label': Craft.t('app', 'Back to sources'),
    })
      .prependTo($sourceSettingsHeader)
      .on('activate', () => {
        this.setSelectedScreen(this.$sourcesSidebar);
      });

    Craft.ui.icon('chevron-left').then((html: string) => {
      $backBtn.append($('<div class="cp-icon"/>').html(html));
    });

    this.$sourceSettingsContainer = $(
      '<div class="cs-source-settings">'
    ).appendTo(this.$sourceSettingsOuterContainer);

    this.$footer = $('<div class="footer"/>').appendTo(this.#$container);
    this.$footerBtnContainer = $('<div class="buttons right"/>').appendTo(
      this.$footer
    );
    this.$cancelBtn = $('<button/>', {
      type: 'button',
      class: 'btn',
      text: Craft.t('app', 'Cancel'),
    }).appendTo(this.$footerBtnContainer);
    this.$saveBtn = Craft.ui
      .createSubmitButton({
        class: 'disabled',
        label: Craft.t('app', 'Save'),
        spinner: true,
      })
      .appendTo(this.$footerBtnContainer);

    this.$loadingSpinner = $('<div class="spinner"/>').appendTo(
      this.$sourceSettingsContainer
    );

    this.setContainer(this.#$container[0]);
    this.show();

    Craft.sendActionRequest(
      'POST',
      'element-index-settings/get-customize-sources-modal-data',
      {data: {elementType: this.elementIndex.elementType}}
    )
      .then(async (response: any) => {
        this.$saveBtn.removeClass('disabled');
        await this.buildModal(response.data);
        Garnish.setFocusWithin(this.$sourcesSidebarContent[0]);
      })
      .finally(() => {
        this.$loadingSpinner.remove();
      });

    this.addListener(this.$cancelBtn[0], 'click', 'hide');
    this.addListener(this.$saveBtn[0], 'click', 'save');
    this.addListener(this.#$container[0], 'submit', 'save');
  }

  async buildModal(response: any): Promise<void> {
    this.multiPage = response.multiPage;
    this.baseSortOptions = response.baseSortOptions;
    this.defaultSortOptions = response.defaultSortOptions;
    this.availableTableAttributes = response.availableTableAttributes;
    this.customFieldAttributes = response.customFieldAttributes;
    this.elementTypeName = response.elementTypeName;
    this.conditionBuilderHtml = response.conditionBuilderHtml;
    this.conditionBuilderJs = response.conditionBuilderJs;
    this.sites = response.sites;
    this.userGroups = response.userGroups;
    this.viewModes = response.viewModes;

    if (response.headHtml) {
      await Craft.appendHeadHtml(response.headHtml);
    }
    if (response.bodyHtml) {
      await Craft.appendBodyHtml(response.bodyHtml);
    }

    if (this.multiPage) {
      await this.createPagesSidebar(response);

      $('<button/>', {
        type: 'button',
        class: 'cs-back-btn',
        title: Craft.t('app', 'Back to pages'),
        'aria-label': Craft.t('app', 'Back to pages'),
      })
        .append(
          $('<div class="cp-icon"/>').html(await Craft.ui.icon('chevron-left'))
        )
        .prependTo(this.$sourcesHeader)
        .on('activate', () => {
          this.setSelectedScreen(this.$pagesSidebar);
        });
    }

    if (Craft.hasMousePointerEvents()) {
      this.sourceDrag = new SourceDrag(this, {handle: '.move'});
    } else {
      this.$sourcesSidebar.find('.cs-item .move').hide();
    }

    this.sources = [];
    for (let i = 0; i < response.sources.length; i++) {
      this.addSource(response.sources[i]);
    }
    if (!this.selectedSource && this.sources.length) {
      this.sources[0].select();
    }

    this.$newSourceBtn = $('<button/>', {
      class: 'btn add icon dashed menubtn',
      type: 'button',
      title: Craft.t('app', 'Source actions'),
      'aria-label': Craft.t('app', 'Source actions'),
      'aria-controls': 'cs-source-actions',
      'data-disclosure-trigger': 'true',
    }).appendTo(this.$sourcesSidebarContent);

    this.$sourceMenu = $('<div/>', {
      id: 'cs-source-actions',
      class: 'menu menu--disclosure',
    }).appendTo(this.$sourcesSidebarContent);

    this.sourceMenu = new Garnish.DisclosureMenu(this.$newSourceBtn);

    const addSource = (sourceData: any) => {
      const source = this.addSource(sourceData, true);
      Garnish.scrollContainerToElement(
        this.$sourcesSidebarContent,
        source.$item
      );
      source.select();
      this.sourceMenu.hide();
    };

    this.sourceMenu.addItem({
      label: Craft.t('app', 'New heading'),
      onActivate: () => {
        addSource({type: 'heading'});
        this.focusLabelInput();
      },
    });

    if (response.conditionBuilderHtml) {
      this.sourceMenu.addItem({
        label: Craft.t('app', 'New custom source'),
        onActivate: () => {
          const sortOptions = this.baseSortOptions!.slice(0);
          sortOptions.push(...(this as any).defaultSortOptions);
          addSource({
            type: 'custom',
            key: `custom:${Craft.uuid()}`,
            sortOptions,
            defaultSort: [sortOptions[0].attr, sortOptions[1].defaultDir],
            tableAttributes: [],
            availableTableAttributes: [],
          });
          this.focusLabelInput();
        },
      });
    }
  }

  async createPagesSidebar(response: any): Promise<void> {
    this.$sourcesSidebar.removeClass('cs-selected-screen');
    const headerId = `cs-header-${Math.floor(Math.random() * 1000000)}`;
    this.$pagesSidebar = $('<div/>', {
      class: 'cs-sidebar cs-selected-screen',
      role: 'navigation',
      'aria-labelledby': headerId,
    }).insertBefore(this.$sourcesSidebar);

    $('<div class="cs-header"/>')
      .appendTo(this.$pagesSidebar)
      .append(
        $('<h2/>', {id: headerId, class: 'h3'}).text(Craft.t('app', 'Pages'))
      );

    this.$pagesSidebarContent = $('<div class="cs-sidebar-content"/>').appendTo(
      this.$pagesSidebar
    );
    this.$pagesSidebarItems = $('<ol class="cs-sidebar-list"/>').appendTo(
      this.$pagesSidebarContent
    );

    if (Craft.hasMousePointerEvents()) {
      this.pageDrag = new DragSort(null, {
        handle: '.move',
        axis: 'y' as const,
      });
    } else {
      this.$pagesSidebar.find('.cs-item .move').hide();
    }

    this.pages = [];
    const pageNames: string[] = Craft.uniqueArray(
      response.sources.map((s: any) => s.page)
    );
    for (const name of pageNames) {
      const icon = response.pageSettings
        ? response.pageSettings[name]?.icon
        : null;
      await this.addPage(name, icon);
    }
    if (!this.selectedPage && this.pages.length) {
      this.pages[0].select();
    }

    this.$newPageBtn = $('<button/>', {
      class: 'btn add icon dashed',
      type: 'button',
      text: Craft.t('app', 'New page'),
    }).appendTo(this.$pagesSidebarContent);

    this.$newPageBtn.on('activate', () => {
      new PageSettingsModal(this, {
        triggerElement: this.$newPageBtn,
        validateName: (name: string) => {
          if (Page.nameId(name ?? '') === '') {
            return Craft.t('yii', '{attribute} cannot be blank.', {
              attribute: Craft.t('app', 'Page Name'),
            });
          }
          if (!this.isPageNameUnique(name)) {
            return Craft.t('app', 'Another page already has that name.');
          }
          return true;
        },
        onSave: async (name: string, icon: string | null) => {
          await this.addPage(name, icon, true);
        },
      });
    });
  }

  isPageNameUnique(name: string, page?: any): boolean {
    const nameId = Page.nameId(name);
    return !this.pages!.find(
      (p: any) => p !== page && Page.nameId(p.name) === nameId
    );
  }

  async addPage(
    name: string,
    icon: string | null = null,
    isNew = false
  ): Promise<any> {
    const $item = $('<li class="cs-item"/>').appendTo(this.$pagesSidebarItems);
    const $itemButton = $('<div class="cs-item__btn cs-item__page-btn"/>')
      .attr({tabindex: '0', role: 'button'})
      .append(
        $('<div class="cp-icon"/>').html(icon ? await Craft.ui.icon(icon) : '')
      )
      .append(
        $('<div/>', {
          id: `cs-item-label-${Math.floor(Math.random() * 1000000)}`,
          class: 'label',
          text: name,
        })
      )
      .appendTo($item);

    if (Craft.hasMousePointerEvents()) {
      $(
        `<a class="move icon cs-item__move" title="${Craft.t('app', 'Reorder')}" role="button"></a>`
      ).appendTo($item);
    }

    $('<input/>', {
      type: 'hidden',
      name: `pageSettings[${name}][icon]`,
      value: icon || '',
      'data-icon-input': 'true',
    }).appendTo($item);

    const page = new Page(this, $item, $itemButton, name, icon, isNew);
    this.pageDrag?.addItems($item);

    if (
      this.elementIndex.settings.page &&
      Page.nameId(this.elementIndex.settings.page) === Page.nameId(name)
    ) {
      page.select();
    }

    this.pages!.push(page);
    this.updatePageActionButtons();

    if (isNew) {
      Craft.cp.announce(Craft.t('app', 'Success'));
    }

    return page;
  }

  focusLabelInput(): void {
    this.selectedSource.$labelInput.focus();
  }

  getSourceName(): string {
    return this.selectedSource
      ? this.selectedSource.sourceData.label
      : this.sources![0].sourceData.label;
  }

  getSourceContainer(pageName: string, create = true): any {
    if (this.sourceContainers[pageName] === undefined && create) {
      this.sourceContainers[pageName] = $('<ol class="cs-sidebar-list">');
      if (this.$newSourceBtn) {
        this.sourceContainers[pageName].insertBefore(this.$newSourceBtn);
      } else {
        this.sourceContainers[pageName].appendTo(this.$sourcesSidebarContent);
      }
      if (this.multiPage && pageName !== this.selectedPage.name) {
        this.sourceContainers[pageName].addClass('hidden');
      }
    }
    return this.sourceContainers[pageName];
  }

  addSource(sourceData: any, isNew?: boolean): any {
    const pageName =
      sourceData.page ?? this.selectedPage?.name ?? '__DEFAULT__';
    sourceData.page = pageName;
    const isHeading = sourceData.type === 'heading';

    const $sourceContainer = this.getSourceContainer(pageName);
    const $item = $('<li class="cs-item"/>').appendTo($sourceContainer);
    const $itemButton = $('<div class="cs-item__btn"/>')
      .attr({tabindex: '0', role: 'button'})
      .append(
        $('<div/>', {
          id: `cs-item-label-${Math.floor(Math.random() * 1000000)}`,
          class: 'label',
        })
      )
      .append($('<div class="handle"/>'))
      .appendTo($item);

    if (isHeading && !sourceData.key) {
      sourceData.key = `heading:${Craft.uuid()}`;
    }

    const $itemInput = $('<input/>', {
      type: 'hidden',
      name: 'sourceOrder[]',
      value: sourceData.key,
    }).appendTo($item);

    if (Craft.hasMousePointerEvents()) {
      $(
        `<a class="move icon cs-item__move" title="${Craft.t('app', 'Reorder')}" role="button"></a>`
      ).appendTo($item);
    }

    let source: any;

    if (isHeading) {
      $item.addClass('cs-item--heading');
      $('<input type="hidden"/>')
        .attr('name', `sources[${sourceData.key}][heading]`)
        .val(sourceData.heading)
        .appendTo($item);
      source = new Heading(
        this,
        $item,
        $itemButton,
        $itemInput,
        sourceData,
        isNew
      );
      source.updateItemLabel(sourceData.heading);
    } else {
      if (sourceData.type === 'native') {
        source = new Source(
          this,
          $item,
          $itemButton,
          $itemInput,
          sourceData,
          isNew
        );
      } else {
        source = new CustomSource(
          this,
          $item,
          $itemButton,
          $itemInput,
          sourceData,
          isNew
        );
      }
      source.updateItemLabel(sourceData.label);
      if (sourceData.data?.handle) {
        source.updateItemHandle(sourceData.data.handle);
      }
      if (sourceData.key === this.elementIndex.rootSourceKey) {
        source.select();
      }
    }

    this.sourceDrag?.addItems($item);
    this.sources!.push(source);
    this.updateSourceActionButtons();

    if (isNew) {
      Craft.cp.announce(Craft.t('app', 'Success'));
    }

    return source;
  }

  updatePageActionButtons(): void {
    for (const page of this.pages!) {
      page.updateActionButton();
    }
  }

  updateSourceActionButtons(): void {
    for (const source of this.sources!) {
      source.updateActionButton();
    }
  }

  setSelectedScreen($screen: any): void {
    if (this.#$container.width() >= 700) return;

    this.$pagesSidebar?.removeClass('cs-selected-screen');
    this.$sourcesSidebar.removeClass('cs-selected-screen');
    this.$sourceSettingsOuterContainer.removeClass('cs-selected-screen');
    $screen.addClass('cs-selected-screen');
    Craft.setFocusWithin($screen);
  }

  save(ev?: Event): void {
    if (ev) ev.preventDefault();

    if (
      this.$saveBtn.hasClass('disabled') ||
      this.$saveBtn.hasClass('loading')
    ) {
      return;
    }

    this.$saveBtn.addClass('loading');

    if (this.multiPage && this.pages!.some((page: any) => page.name === '')) {
      Craft.cp.displayError(
        Craft.t('yii', '{attribute} cannot be blank.', {
          attribute: Craft.t('app', 'Page Name'),
        })
      );
      this.$saveBtn.removeClass('loading');
      return;
    }

    Craft.sendActionRequest(
      'POST',
      'element-index-settings/save-customize-sources-modal-settings',
      {
        data:
          this.#$container.serialize() +
          `&elementType=${this.elementIndex.elementType}`,
      }
    )
      .then(async ({data}: any) => {
        let sourceKey: string | null = null;
        if (
          this.selectedSource &&
          this.selectedSource.sourceData.key &&
          !data.disabledSourceKeys.includes(this.selectedSource.sourceData.key)
        ) {
          sourceKey = this.selectedSource.sourceData.key;
        } else if (!this.elementIndex.sourceKey) {
          sourceKey = this.elementIndex.$visibleSources.first().data('key');
        }

        if (sourceKey) {
          await this.elementIndex.asyncSelectSourceByKey(sourceKey);
        }

        window.location.reload();
      })
      .catch((e: any) => {
        Craft.cp.displayError(e?.response?.data?.message);
      })
      .finally(() => {
        this.$saveBtn.removeClass('loading');
      });
  }

  override destroy(): void {
    this.destroying = true;

    const sources = [...this.sources!];
    for (const source of sources) source.destroy();

    if (this.pages) {
      const pages = [...this.pages];
      for (const page of pages) page.destroy();
    }

    if (this.sourceMenu) {
      this.sourceMenu.destroy();
      this.$sourceMenu.remove();
    }

    super.destroy();
  }

  // Typed as any to silence TS complaints about undeclared dynamic props set in buildModal()
  declare defaultSortOptions: any;
  declare sites: any;
}

// ─── PageSettingsModal ────────────────────────────────────────────────────────

export class PageSettingsModal extends Modal {
  declare settings: any;

  modal: any = null;
  name: string | null = null;
  icon: string | null = null;

  #$container: any = null;

  constructor(modal: any, nameOrSettings?: any, icon?: any, settings?: any) {
    // (modal, settings) overload
    if (typeof nameOrSettings === 'object' && !Array.isArray(nameOrSettings)) {
      settings = nameOrSettings;
      nameOrSettings = null;
    }

    super(undefined as any, {autoShow: false});

    this.modal = modal;
    this.name = nameOrSettings ?? null;
    this.icon = icon ?? null;

    this.#$container = $('<form class="modal fitted"/>').appendTo($(bod));
    const $body = $('<div class="body"/>').appendTo(this.#$container);

    const $nameField = Craft.ui
      .createTextField({
        label: Craft.t('app', 'Page Name'),
        value: this.name,
        required: true,
      })
      .appendTo($body);

    const $iconField = Craft.ui
      .createIconPickerField({
        label: Craft.t('app', 'Icon'),
        value: this.icon,
      })
      .appendTo($body);

    const $footer = $(
      '<div class="footer flex rightalign flex-nowrap"/>'
    ).appendTo(this.#$container);

    const $cancelBtn = Craft.ui
      .createButton({label: Craft.t('app', 'Cancel')})
      .appendTo($footer);

    Craft.ui
      .createSubmitButton({label: Craft.t('app', 'Save')})
      .appendTo($footer);

    const $nameInput = $nameField.find('.text');
    $nameInput.on('input', () => {
      this.name = $nameInput.val();
    });

    $iconField.find('craft-icon-picker').on('change', (ev: any) => {
      this.icon = ev.originalEvent?.detail?.value ?? '';
    });

    $cancelBtn.on('activate', () => {
      this.hide();
    });

    this.#$container.on('submit', (ev: any) => {
      ev.preventDefault();

      if (this.settings.validateName) {
        const result = this.settings.validateName(this.name);
        if (result !== true) {
          Craft.ui.addErrorsToField($nameField, [result]);
          this.updateSizeAndPosition();
          $nameInput.focus();
          return;
        }

        if (this.settings.onSave) {
          this.settings.onSave(this.name, this.icon);
        }
        this.hide();
      }
    });

    this.setContainer(this.#$container[0]);

    if (settings) {
      this.setSettings(settings, {});
    }

    this.show();
  }

  override onFadeOut(): void {
    super.onFadeOut();
    this.destroy();
  }
}

// ─── SourceDrag ───────────────────────────────────────────────────────────────

export class SourceDrag extends DragSort {
  modal: any = null;
  activePage: any = null;

  constructor(modal: any, settings: any = {}) {
    settings.filter = function (this: SourceDrag) {
      const $item = $(this.$targetItem as any);
      if ($item.hasClass('cs-item--heading')) {
        return $item.add($item.nextUntil('.cs-item--heading'));
      }
      return $item;
    } as any;

    if (!modal.multiPage) {
      settings.axis = 'y' as const;
    }

    super(null, settings);
    this.modal = modal;
  }

  override onDragStart(): void {
    this.activePage = null;
    super.onDragStart();
  }

  override onDrag(): void {
    if (this.modal.multiPage) {
      let newActivePage: any = null;
      for (const page of this.modal.pages) {
        if (
          page !== this.modal.selectedPage &&
          Garnish.hitTest(this.mouseX, this.mouseY, page.$item[0])
        ) {
          newActivePage = page;
          break;
        }
      }

      if (
        (this.activePage && newActivePage !== this.activePage) ||
        (!this.activePage && newActivePage !== null)
      ) {
        if (this.activePage) {
          this.activePage.$item.removeClass('active');
        }
        if (newActivePage) {
          this.activePage = newActivePage;
          this.activePage.$item.addClass('active');
        } else {
          this.activePage = null;
        }
      }
    }
    super.onDrag();
  }

  override onDragStop(): void {
    if (this.activePage) {
      const $draggee = $(this.$draggee as any);
      $draggee.each((_: number, draggee: Element) => {
        $(draggee).data('source').moveToPage(this.activePage);
      });
      $draggee.show().css('visibility', '');
      this.activePage.$item.removeClass('active');
      this.fadeOutHelpers();
    }
    super.onDragStop();
  }

  override returnHelpersToDraggees(): void {
    if (this.activePage) return;
    super.returnHelpersToDraggees();
  }
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export class Page extends Base {
  declare settings: any;

  modal: any = null;
  actionMenu: any = null;

  $item: any = null;
  $itemButton: any = null;
  $actionBtn: any = null;
  $actionMenu: any = null;
  _name: string | null = null;
  _icon: string | null = null;
  isNew: boolean | null = null;

  moveUpBtn: any = null;
  moveDownBtn: any = null;
  removeBtn: any = null;

  static nameId(n: string): string {
    return n.replace(/[^\p{L}\p{N}\p{M}]/gu, '').toLowerCase();
  }

  constructor(
    modal: any,
    $item: any,
    $itemButton: any,
    name: string,
    icon: string | null,
    isNew: boolean
  ) {
    super();
    this.modal = modal;
    this.$item = $item;
    this.$itemButton = $itemButton;
    this._name = name;
    this._icon = icon;
    this.isNew = isNew;

    this.$item.data('page', this);
    this.createActionMenu();

    this.$itemButton.on('activate', () => this.select());
  }

  createActionMenu(): void {
    this.$actionBtn = $('<button/>', {
      class: 'btn action-btn',
      type: 'button',
      title: Craft.t('app', 'Actions'),
      'aria-label': Craft.t('app', 'Actions'),
      'aria-describedby': this.$item.find('.label').attr('id'),
      'aria-controls': 'cs-source-actions',
      'data-disclosure-trigger': 'true',
    }).insertAfter(this.$item.find('.cs-item__btn'));

    this.$actionMenu = $('<div/>', {
      id: 'cs-source-actions',
      class: 'menu menu--disclosure',
    }).appendTo(this.$item);

    this.actionMenu = new Garnish.DisclosureMenu(this.$actionBtn);

    this.moveUpBtn = this.actionMenu.addItem({
      icon: 'arrow-up',
      label: Craft.t('app', 'Move up'),
      onActivate: () => {
        const prev = this.getPrevPage();
        if (prev) this.$item.insertBefore(prev.$item);
      },
    });

    this.moveDownBtn = this.actionMenu.addItem({
      icon: 'arrow-down',
      label: Craft.t('app', 'Move down'),
      onActivate: () => {
        const next = this.getNextPage();
        if (next) this.$item.insertAfter(next.$item);
      },
    });

    this.actionMenu.addItem({
      icon: 'gear',
      label: Craft.t('app', 'Page settings'),
      onActivate: () => {
        new PageSettingsModal(this, this.name, this.icon, {
          triggerElement: this.$actionBtn,
          validateName: (name: string) => {
            if (Page.nameId(name ?? '') === '') {
              return Craft.t('yii', '{attribute} cannot be blank.', {
                attribute: Craft.t('app', 'Page Name'),
              });
            }
            if (!this.modal.isPageNameUnique(name, this)) {
              return Craft.t('app', 'Another page already has that name.');
            }
            return true;
          },
          onSave: (name: string, icon: string | null) => {
            this.name = name;
            this.icon = icon;
          },
        });
      },
    });

    this.removeBtn = this.actionMenu.addItem({
      icon: 'trash',
      label: Craft.t('app', 'Remove page'),
      destructive: true,
      onActivate: () => {
        if (
          confirm(
            Craft.t(
              'app',
              'Are you sure you want to remove the page “{name}”?',
              {
                name: this.name,
              }
            )
          )
        ) {
          this.destroy();
        }
      },
    });

    this.actionMenu.on('show', () => {
      this.updateActionButton();
    });
  }

  updateActionButton(): void {
    this.actionMenu.toggleItem(this.moveUpBtn, !!this.getPrevPage());
    this.actionMenu.toggleItem(this.moveDownBtn, !!this.getNextPage());
    this.actionMenu.toggleItem(this.removeBtn, this.modal.pages.length > 1);

    if (this.actionMenu.hasVisibleItems()) {
      this.$actionBtn.removeClass('hidden');
    } else {
      this.$actionBtn.addClass('hidden');
    }
  }

  getPrevPage(): any {
    return this.$item.prev('.cs-item').data('page');
  }

  getNextPage(): any {
    return this.$item.next('.cs-item').data('page');
  }

  getSourceContainer(create = true): any {
    return this.modal.getSourceContainer(this.name, create);
  }

  isSelected(): boolean {
    return this.modal.selectedPage === this;
  }

  select(): void {
    this.modal.setSelectedScreen(this.modal.$sourcesSidebar);
    if (this.isSelected()) return;

    if (this.modal.selectedPage) {
      this.modal.selectedPage.deselect();
    }

    this.$item.addClass('sel');
    this.$itemButton.attr({'aria-current': 'true'});

    if (this.name && this.modal.sourceContainers[this.name]) {
      this.modal.sourceContainers[this.name].removeClass('hidden');
    }
    this.modal.$sourceSettingsContainer.scrollTop(0);
  }

  deselect(): void {
    this.$item.removeClass('sel');
    this.$itemButton.attr({'aria-current': 'false'});

    if (this.name && this.modal.sourceContainers[this.name]) {
      this.modal.sourceContainers[this.name].addClass('hidden');
    }

    if (this.modal.selectedSource) {
      this.modal.selectedSource.deselect();
    }

    this.modal.selectedPage = null;
  }

  set name(name: string | null) {
    if (name === this._name) return;

    this.$item.find('.label').text(name);
    this.$item
      .find('[data-icon-input]')
      .attr('name', `pageSettings[${name}][icon]`);

    const $sourceContainer = this.getSourceContainer(false);
    if ($sourceContainer) {
      this.modal.sourceContainers[name!] = $sourceContainer;
      delete this.modal.sourceContainers[this._name!];
      $sourceContainer.find('.cs-item').each((_: number, item: Element) => {
        $(item).data('source').$pageInput.val(name);
      });
    }

    this._name = name;
  }

  get name(): string | null {
    return this._name;
  }

  set icon(icon: string | null) {
    if (icon === this._icon) return;

    this.$item.find('[data-icon-input]').val(icon || '');
    const $icon = this.$item.find('.cp-icon');
    if (icon) {
      Craft.ui.icon(icon).then((html: string) => {
        $icon.html(html);
      });
    } else {
      $icon.html('');
    }

    this._icon = icon;
  }

  get icon(): string | null {
    return this._icon;
  }

  override destroy(): void {
    this.modal.pageDrag?.removeItems(this.$item);
    this.modal.pages = this.modal.pages.filter((p: any) => p !== this);

    let $closestItem = this.$item.prev('.cs-item');
    if (!$closestItem.length) $closestItem = this.$item.next('.cs-item');

    const closestPage = $closestItem.data('page');

    if (this.isSelected()) {
      this.deselect();
      closestPage?.select();
    }

    closestPage?.$actionBtn.focus();

    const $sourceContainer = this.getSourceContainer(false);
    if ($sourceContainer) {
      if (closestPage) {
        const $newSourceContainer = closestPage.getSourceContainer();
        const $sources = $sourceContainer.children();
        for (let i = 0; i < $sources.length; i++) {
          const $source = $sources.eq(i).appendTo($newSourceContainer);
          const source = $source.data('source');
          source.$pageInput.val(closestPage.name);
        }
      }
      $sourceContainer.remove();
    }

    this.$item.data('page', null);
    this.$item.remove();

    this.actionMenu.destroy();
    this.$actionMenu.remove();

    this.modal.updatePageActionButtons();

    super.destroy();
  }
}

// ─── BaseSource ───────────────────────────────────────────────────────────────

export class BaseSource extends Base {
  declare settings: any;

  modal: any = null;
  actionMenu: any = null;

  $item: any = null;
  $itemButton: any = null;
  $itemInput: any = null;
  $pageInput: any = null;
  $actionBtn: any = null;
  $actionMenu: any = null;
  $settingsContainer: any = null;

  sourceData: any = null;
  isNew: boolean | null = null;

  moveUpBtn: any = null;
  moveDownBtn: any = null;

  constructor(
    modal: any,
    $item: any,
    $itemButton: any,
    $itemInput: any,
    sourceData: any,
    isNew?: boolean
  ) {
    super();
    this.modal = modal;
    this.$item = $item;
    this.$itemButton = $itemButton;
    this.$itemInput = $itemInput;
    this.sourceData = sourceData;
    this.isNew = isNew ?? null;

    this.$item.data('source', this);
    this.createActionMenu();

    if (this.modal.multiPage) {
      this.$pageInput = $('<input/>', {
        type: 'hidden',
        name: `sourcePages[${this.sourceData.key}]`,
        value: sourceData.page,
      }).appendTo(this.$item);
    }

    this.$itemButton.on('activate', () => this.select());
  }

  createActionMenu(): void {
    this.$actionBtn = $('<button/>', {
      class: 'btn action-btn',
      type: 'button',
      title: Craft.t('app', 'Actions'),
      'aria-label': Craft.t('app', 'Actions'),
      'aria-describedby': this.$item.find('.label').attr('id'),
      'aria-controls': 'cs-source-actions',
      'data-disclosure-trigger': 'true',
    }).insertAfter(this.$item.find('.cs-item__btn'));

    this.$actionMenu = $('<div/>', {
      id: 'cs-source-actions',
      class: 'menu menu--disclosure',
    }).appendTo(this.$item);

    this.actionMenu = new Garnish.DisclosureMenu(this.$actionBtn);

    this.moveUpBtn = this.actionMenu.addItem({
      icon: 'arrow-up',
      label: Craft.t('app', 'Move up'),
      onActivate: () => {
        const prev = this.getPrevSource();
        if (prev) this.$item.insertBefore(prev.$item);
      },
    });

    this.moveDownBtn = this.actionMenu.addItem({
      icon: 'arrow-down',
      label: Craft.t('app', 'Move down'),
      onActivate: () => {
        const next = this.getNextSource();
        if (next) this.$item.insertAfter(next.$item);
      },
    });

    if (this.isHeading() || this.isCustomSource()) {
      this.actionMenu.addItem({
        icon: 'trash',
        label: Craft.t(
          'app',
          this.isHeading() ? 'Remove heading' : 'Delete custom source'
        ),
        destructive: true,
        onActivate: () => {
          this.destroy();
        },
      });
    }

    this.actionMenu.on('show', () => {
      this.updateActionButton();
    });
  }

  getPrevSource(): any {
    return this.$item.prev('.cs-item').data('source');
  }

  getNextSource(): any {
    return this.$item.next('.cs-item').data('source');
  }

  updateActionButton(): void {
    this.actionMenu.toggleItem(this.moveUpBtn, !!this.getPrevSource());
    this.actionMenu.toggleItem(this.moveDownBtn, !!this.getNextSource());

    if (this.modal.multiPage) {
      const currentPage = this.$pageInput.val();
      let $ul = this.$actionMenu.find('[data-cs-multi-page-list]');
      if (!$ul.length) {
        this.actionMenu.addHr();
        $ul = $(this.actionMenu.addList()).attr(
          'data-cs-multi-page-list',
          'true'
        );
      }
      $ul.html('');
      this.modal.pages.forEach((page: any) => {
        if (page.name !== currentPage) {
          const button = this.actionMenu.addItem(
            {
              icon: page.icon
                ? async () => await Craft.ui.icon(page.icon)
                : null,
              label: Craft.t('app', 'Move to {page}', {page: page.name}),
            },
            $ul[0]
          );
          $(button).on('activate', () => {
            this.actionMenu.hide();
            this.moveToPage(page);
          });
        }
      });
    }

    if (this.actionMenu.hasVisibleItems()) {
      this.$actionBtn.removeClass('hidden');
    } else {
      this.$actionBtn.addClass('hidden');
    }
  }

  isHeading(): boolean {
    return false;
  }

  isCustomSource(): boolean {
    return false;
  }

  isSelected(): boolean {
    return this.modal.selectedSource === this;
  }

  select(): void {
    this.modal.setSelectedScreen(this.modal.$sourceSettingsOuterContainer);
    if (this.isSelected()) return;

    if (this.modal.selectedSource) {
      this.modal.selectedSource.deselect();
    }

    this.$item.addClass('sel');
    this.$itemButton.attr({'aria-current': 'true'});
    this.modal.selectedSource = this;

    if (!this.$settingsContainer) {
      this.$settingsContainer = $('<div/>').appendTo(
        this.modal.$sourceSettingsContainer
      );
      this.createSettings(this.$settingsContainer);
    } else {
      this.$settingsContainer.removeClass('hidden');
    }

    this.modal.$sourceSettingsContainer.scrollTop(0);
  }

  async createSettings($container: any): Promise<void> {
    void $container;
  }

  getIndexSourceItem(): any {
    return undefined;
  }

  deselect(): void {
    this.$item.removeClass('sel');
    this.$itemButton.attr({'aria-current': 'false'});
    this.modal.selectedSource = null;
    this.$settingsContainer.addClass('hidden');
  }

  updateItemLabel(val: string | null): void {
    if (val) {
      this.$itemButton.find('.label').text(val);
    } else {
      this.$itemButton.find('.label').html('&nbsp;');
    }
  }

  updateItemHandle(val: string | null): void {
    if (val) {
      this.$itemButton.find('.handle').text(val);
    } else {
      this.$itemButton.find('.handle').empty();
    }
  }

  moveToPage(page: any): void {
    this.$item.appendTo(this.modal.getSourceContainer(page.name));
    this.$pageInput.val(page.name);
    if (this.isSelected()) this.deselect();
    this.modal.updateSourceActionButtons();
  }

  override destroy(): void {
    this.modal.sourceDrag?.removeItems(this.$item);
    this.modal.sources = this.modal.sources.filter((s: any) => s !== this);

    if (this.isSelected()) {
      this.deselect();
      if (!this.modal.destroying) {
        let $closestItem = this.$item.prev('.cs-item');
        if (!$closestItem.length) $closestItem = this.$item.next('.cs-item');
        if ($closestItem.length) $closestItem.data('source').select();
        Garnish.setFocusWithin(this.modal.$sourceSettingsContainer[0]);
      }
    }

    this.$item.data('source', null);
    this.$item.remove();

    this.actionMenu.destroy();
    this.$actionMenu.remove();

    if (this.$settingsContainer) this.$settingsContainer.remove();

    this.modal.updateSourceActionButtons();
    super.destroy();
  }
}

// ─── Source ───────────────────────────────────────────────────────────────────

export class Source extends BaseSource {
  $viewModeInput: any = null;
  viewModeListbox: any = null;
  $sortAttributeSelect: any = null;
  $sortDirectionPicker: any = null;
  $sortDirectionInput: any = null;
  sortDirectionListbox: any = null;

  override async createSettings($container: any): Promise<void> {
    Craft.ui
      .createLightswitchField({
        label: Craft.t('app', 'Enabled'),
        name: `sources[${this.sourceData.key}][enabled]`,
        on: !this.sourceData.disabled,
      })
      .appendTo($container);
    this.createViewModeField($container);
    this.createSortField($container);
    this.createTableAttributesField($container);
  }

  createViewModeField($container: any): void {
    const $inputContainer = $('<section/>', {
      class: 'btngroup btngroup--exclusive',
      'aria-label': Craft.t('app', 'View mode options'),
    });

    const viewModes = this.modal.viewModes.filter(
      (viewMode: any) => !viewMode.structuresOnly || this.sourceData.structureId
    );
    let defaultViewMode = this.sourceData.defaultViewMode;
    if (
      !defaultViewMode ||
      !viewModes.some((viewMode: any) => viewMode.mode === defaultViewMode)
    ) {
      defaultViewMode = viewModes[0]?.mode;
    }

    for (const viewMode of viewModes) {
      const $btn = $('<button/>', {
        type: 'button',
        class: 'btn',
        title: viewMode.title,
        'aria-label': viewMode.title,
        'data-mode': viewMode.mode,
      }).appendTo($inputContainer);
      $('<div/>', {class: 'cp-icon small'})
        .append(viewMode.iconSvg)
        .appendTo($btn);
      if (viewMode.mode === defaultViewMode) {
        $btn.addClass('active').attr('aria-pressed', 'true');
      } else {
        $btn.attr('aria-pressed', 'false');
      }
    }

    $inputContainer.children('button:last').addClass('btngroup-btn-last');

    this.$viewModeInput = $('<input/>', {
      type: 'hidden',
      name: `sources[${this.sourceData.key}][defaultViewMode]`,
      value: this.sourceData.defaultViewMode,
    }).appendTo($inputContainer);

    this.viewModeListbox = new Craft.Listbox($inputContainer, {
      onChange: ($selectedOption: any) => {
        this.$viewModeInput.val($selectedOption.data('mode'));
      },
    });

    Craft.ui
      .createField($inputContainer, {
        label: Craft.t('app', 'Default View Mode'),
        fieldset: true,
      })
      .appendTo($container)
      .addClass('view-mode-field');
  }

  createSortField($container: any): void {
    const $inputContainer = $('<div class="flex"/>');

    const options = this.sourceData.sortOptions.sort((a: any, b: any) => {
      return a.label === b.label ? 0 : a.label < b.label ? -1 : 1;
    });

    const groups = options.reduce(
      (acc: any, o: any) => {
        if (o.attr === 'structure') {
          acc.structure.push(o);
        } else if (o.attr.startsWith('field:')) {
          acc.field.push(o);
        } else {
          acc.attribute.push(o);
        }
        return acc;
      },
      {structure: [], attribute: [], field: []}
    );

    if (groups.field.length) {
      groups.field.unshift({optgroup: Craft.t('app', 'Fields')});
    }

    const $sortAttributeSelectContainer = Craft.ui
      .createSelect({
        name: `sources[${this.sourceData.key}][defaultSort][0]`,
        options: [
          ...groups.structure,
          ...groups.attribute,
          ...groups.field,
        ].map((o: any) => (o.optgroup ? o : {label: o.label, value: o.attr})),
        value: this.sourceData.defaultSort[0],
      })
      .addClass('fullwidth')
      .appendTo($('<div/>').appendTo($inputContainer));

    this.$sortAttributeSelect = $sortAttributeSelectContainer
      .children('select')
      .attr('aria-label', Craft.t('app', 'Sort attribute'));

    this.$sortDirectionPicker = $('<section/>', {
      class: 'btngroup btngroup--exclusive',
      'aria-label': Craft.t('app', 'Sort direction'),
    })
      .append(
        $('<button/>', {
          type: 'button',
          class: 'btn',
          title: Craft.t('app', 'Sort ascending'),
          'aria-label': Craft.t('app', 'Sort ascending'),
          'aria-pressed': 'false',
          'data-icon': 'asc',
          'data-dir': 'asc',
        })
      )
      .append(
        $('<button/>', {
          type: 'button',
          class: 'btn',
          title: Craft.t('app', 'Sort descending'),
          'aria-label': Craft.t('app', 'Sort descending'),
          'aria-pressed': 'false',
          'data-icon': 'desc',
          'data-dir': 'desc',
        })
      )
      .appendTo($inputContainer);

    this.$sortDirectionInput = $('<input/>', {
      type: 'hidden',
      name: `sources[${this.sourceData.key}][defaultSort][1]`,
    }).appendTo($inputContainer);

    this.sortDirectionListbox = new Craft.Listbox(this.$sortDirectionPicker, {
      onChange: ($selectedOption: any) => {
        this.$sortDirectionInput.val($selectedOption.data('dir'));
      },
    });

    this.$sortAttributeSelect.on('change', () => {
      this.handleSortAttributeChange();
    });

    this.handleSortAttributeChange(true);

    Craft.ui
      .createField($inputContainer, {
        label: Craft.t('app', 'Default Sort'),
        fieldset: true,
      })
      .appendTo($container)
      .addClass('sort-field');
  }

  handleSortAttributeChange(useDefaultDir?: boolean): void {
    const attr = this.$sortAttributeSelect.val();

    if (attr === 'structure') {
      this.sortDirectionListbox.select(0);
      this.sortDirectionListbox.disable();
      this.$sortDirectionPicker.addClass('disabled');
    } else {
      this.sortDirectionListbox.enable();
      this.$sortDirectionPicker.removeClass('disabled');

      const dir = useDefaultDir
        ? this.sourceData.defaultSort[1]
        : this.sourceData.sortOptions.find((o: any) => o.attr === attr)
            .defaultDir;
      this.sortDirectionListbox.select(dir === 'asc' ? 0 : 1);
    }
  }

  createTableAttributesField($container: any): void {
    const availableTableAttributes = this.availableTableAttributes().sort(
      (a: any, b: any) => (a[1] === b[1] ? 0 : a[1] < b[1] ? -1 : 1)
    );

    if (
      !this.sourceData.tableAttributes.length &&
      !availableTableAttributes.length
    ) {
      return;
    }

    const name = `sources[${this.sourceData.key}][tableAttributes][]`;

    $('<input/>', {type: 'hidden', name, value: ''}).appendTo($container);

    Craft.ui
      .createSortableCheckboxSelectField({
        label: Craft.t('app', 'Default Table Columns'),
        instructions: Craft.t(
          'app',
          'Choose which table columns should be visible for this source by default.'
        ),
        name,
        options: availableTableAttributes.map(
          ([key, label]: [string, string]) => ({
            label,
            value: key,
          })
        ),
        values: this.sourceData.tableAttributes.map(([key]: [string]) => key),
      })
      .appendTo($container);
  }

  availableTableAttributes(): any[] {
    const attributes = this.modal.availableTableAttributes.slice(0);
    attributes.push(...this.sourceData.availableTableAttributes);
    return attributes;
  }

  override getIndexSourceItem(): any {
    const $source = this.modal.elementIndex.getSourceByKey(this.sourceData.key);
    if ($source) return $source.closest('li');
    return undefined;
  }
}

// ─── CustomSource ─────────────────────────────────────────────────────────────

export class CustomSource extends Source {
  $labelInput: any = null;

  override isCustomSource(): boolean {
    return true;
  }

  override async createSettings($container: any): Promise<void> {
    const $labelField = Craft.ui
      .createTextField({
        label: Craft.t('app', 'Label'),
        name: `sources[${this.sourceData.key}][label]`,
        value: this.sourceData.label,
      })
      .appendTo($container);

    this.$labelInput = $labelField.find('.text');
    const defaultId = `condition${Math.floor(Math.random() * 1000000)}`;

    const swapPlaceholders = (str: string) =>
      str
        .replace(/__ID__/g, defaultId)
        .replace(
          /__SOURCE_KEY__(?=-)/g,
          Craft.formatInputId(this.sourceData.key)
        )
        .replace(/__SOURCE_KEY__/g, this.sourceData.key);

    const conditionBuilderHtml =
      this.sourceData.conditionBuilderHtml ||
      swapPlaceholders(this.modal.conditionBuilderHtml);
    const conditionBuilderJs =
      this.sourceData.conditionBuilderJs ||
      swapPlaceholders(this.modal.conditionBuilderJs);

    Craft.ui
      .createField($('<div/>').append(conditionBuilderHtml), {
        id: 'criteria',
        label: Craft.t('app', '{type} Criteria', {
          type: this.modal.elementTypeName,
        }),
      })
      .appendTo($container);

    if (conditionBuilderJs) {
      await Craft.appendBodyHtml(conditionBuilderJs);
    }

    this.createSortField($container);
    this.createTableAttributesField($container);
    this.createViewModeField($container);

    if (Craft.sites.length > 1) {
      Craft.ui
        .createCheckboxSelectField({
          label: Craft.t('app', 'Sites'),
          instructions: Craft.t(
            'app',
            'Choose which sites this source should be visible for.'
          ),
          name: `sources[${this.sourceData.key}][sites]`,
          options: Craft.sites.map((site: any) => ({
            label: site.name,
            value: site.uid,
          })),
          values: this.sourceData.sites || '*',
          showAllOption: true,
        })
        .appendTo($container);
    }

    if (this.modal.userGroups.length) {
      Craft.ui
        .createCheckboxSelectField({
          label: Craft.t('app', 'User Groups'),
          instructions: Craft.t(
            'app',
            'Choose which user groups should have access to this source.'
          ),
          name: `sources[${this.sourceData.key}][userGroups]`,
          options: this.modal.userGroups,
          values: this.sourceData.userGroups || '*',
          showAllOption: true,
        })
        .appendTo($container);
    }

    this.addListener(this.$labelInput[0], 'input', 'handleLabelInputChange');
  }

  override availableTableAttributes(): any[] {
    const attributes = super.availableTableAttributes();
    if (this.isNew) {
      const existingFieldAttributes: string[] = [];
      const customFieldAttributes: any[] = [];
      this.modal.customFieldAttributes.forEach((item: any) => {
        if (!existingFieldAttributes.includes(item[0])) {
          existingFieldAttributes.push(item[0]);
          customFieldAttributes.push(item);
        }
      });
      attributes.push(...customFieldAttributes);
    }
    return attributes;
  }

  handleLabelInputChange(): void {
    this.updateItemLabel(this.$labelInput.val());
  }

  override getIndexSourceItem(): any {
    let $source = super.getIndexSourceItem();
    let $label: any;

    if ($source) {
      $label = $source.find('.label');
    } else {
      $label = $('<span/>', {class: 'label'});
      $source = $('<li/>').append(
        $('<a/>', {'data-key': this.sourceData.key}).append($label)
      );
    }

    if (this.$labelInput) {
      let label = this.$labelInput.val().trim();
      if (label === '') label = Craft.t('app', '(blank)');
      $label.text(label);
    }

    return $source;
  }
}

// ─── Heading ──────────────────────────────────────────────────────────────────

export class Heading extends BaseSource {
  $labelInput: any = null;

  override isHeading(): boolean {
    return true;
  }

  override async createSettings($container: any): Promise<void> {
    const $labelField = Craft.ui
      .createTextField({
        label: Craft.t('app', 'Heading'),
        name: `sources[${this.sourceData.key}][heading]`,
        instructions: Craft.t(
          'app',
          'This can be left blank if you just want an unlabeled separator.'
        ),
        value: this.sourceData.heading || '',
      })
      .appendTo($container);

    this.$labelInput = $labelField.find('.text');
    this.addListener(this.$labelInput[0], 'input', 'handleLabelInputChange');
  }

  handleLabelInputChange(): void {
    this.updateItemLabel(this.$labelInput.val());
  }

  override updateItemLabel(val: string | null): void {
    this.$itemButton
      .find('.label')
      .html(
        (val
          ? Craft.escapeHtml(val)
          : `<em>${Craft.t('app', '(blank)')}</em>`) + '&nbsp;'
      );
  }

  override getIndexSourceItem(): any {
    const label =
      (this.$labelInput ? this.$labelInput.val() : null) ||
      this.sourceData.heading ||
      '';
    return $('<li class="heading"/>')
      .append($('<span/>').text(label))
      .append('<ul/>');
  }
}
