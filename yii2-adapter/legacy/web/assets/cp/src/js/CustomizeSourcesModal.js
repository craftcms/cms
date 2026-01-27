/** global: Craft */
/** global: Garnish */
import Garnish from '../../../garnish/src';
import $ from 'jquery';

/**
 * Customize Sources modal
 */
Craft.CustomizeSourcesModal = Garnish.Modal.extend({
  elementIndex: null,

  $body: null,

  $pagesSidebar: null,
  $pagesSidebarContent: null,
  $pagesSidebarItems: null,
  $newPageBtn: null,
  pageIconInputs: null,

  multiPage: false,
  pageDrag: null,
  pages: null,
  selectedPage: null,

  $sourcesSidebar: null,
  $sourcesSidebarContent: null,
  sourceContainers: null,
  $sourcesHeader: null,
  $newSourceBtn: null,

  $sourceSettingsOuterContainer: null,
  $sourceSettingsContainer: null,
  $sourceSettingsHeader: null,
  $sourceMenu: null,
  sourceMenu: null,
  $footer: null,
  $footerBtnContainer: null,
  $saveBtn: null,
  $cancelBtn: null,
  $loadingSpinner: null,

  sourceDrag: null,
  sources: null,
  selectedSource: null,

  elementTypeName: null,
  baseSortOptions: null,
  availableTableAttributes: null,
  customFieldAttributes: null,
  viewModes: null,

  conditionBuilderHtml: null,
  conditionBuilderJs: null,
  userGroups: null,

  init: function (elementIndex, settings) {
    this.base();

    this.setSettings(settings, {
      resizable: true,
    });

    this.elementIndex = elementIndex;

    const $container = $('<form class="modal cs-modal"/>').appendTo(
      Garnish.$bod
    );

    this.$body = $('<div class="cs-body"/>').appendTo($container);

    const headerId = `cs-header-${Math.floor(Math.random() * 1000000)}`;
    this.$sourcesSidebar = $('<div/>', {
      class: 'cs-sidebar cs-selected-screen',
      role: 'navigation',
      'aria-labelledby': headerId,
    }).appendTo(this.$body);
    this.$sourcesHeader = $('<div class="cs-header"/>')
      .appendTo(this.$sourcesSidebar)
      .append(
        $('<h2/>', {
          id: headerId,
          class: 'h3',
        }).text(Craft.t('app', 'Sources'))
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
    Craft.ui.icon('chevron-left').then((html) => {
      $backBtn.append($('<div class="cp-icon"/>').html(html));
    });

    this.$sourceSettingsContainer = $(
      '<div class="cs-source-settings">'
    ).appendTo(this.$sourceSettingsOuterContainer);

    this.$footer = $('<div class="footer"/>').appendTo($container);
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

    this.setContainer($container);
    this.show();

    Craft.sendActionRequest(
      'POST',
      'element-index-settings/get-customize-sources-modal-data',
      {
        data: {
          elementType: this.elementIndex.elementType,
        },
      }
    )
      .then(async (response) => {
        this.$saveBtn.removeClass('disabled');
        await this.buildModal(response.data);
        Garnish.setFocusWithin(this.$sourcesSidebarContent);
      })
      .finally(() => {
        this.$loadingSpinner.remove();
      });

    this.addListener(this.$cancelBtn, 'click', 'hide');
    this.addListener(this.$saveBtn, 'click', 'save');
    this.addListener(this.$container, 'submit', 'save');
  },

  buildModal: async function (response) {
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

    // Create the source item sorter
    if (Craft.hasMousePointerEvents()) {
      this.sourceDrag = new Craft.CustomizeSourcesModal.SourceDrag(this, {
        handle: '.move',
      });
    } else {
      this.$sourcesSidebar.find('.cs-item .move').hide();
    }

    // Create the sources
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

    const addSource = (sourceData) => {
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
        addSource({
          type: 'heading',
        });
        this.focusLabelInput();
      },
    });

    if (response.conditionBuilderHtml) {
      this.sourceMenu.addItem({
        label: Craft.t('app', 'New custom source'),
        onActivate: () => {
          const sortOptions = this.baseSortOptions.slice(0);
          sortOptions.push(...this.defaultSortOptions);

          addSource({
            type: 'custom',
            key: `custom:${Craft.uuid()}`,
            sortOptions: sortOptions,
            defaultSort: [sortOptions[0].attr, sortOptions[1].defaultDir],
            tableAttributes: [],
            availableTableAttributes: [],
          });
          this.focusLabelInput();
        },
      });
    }
  },

  createPagesSidebar: async function (response) {
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
        $('<h2/>', {
          id: headerId,
          class: 'h3',
        }).text(Craft.t('app', 'Pages'))
      );

    this.$pagesSidebarContent = $('<div class="cs-sidebar-content"/>').appendTo(
      this.$pagesSidebar
    );

    this.$pagesSidebarItems = $('<ol class="cs-sidebar-list"/>').appendTo(
      this.$pagesSidebarContent
    );

    // Create the page item sorter
    if (Craft.hasMousePointerEvents()) {
      this.pageDrag = new Garnish.DragSort({
        handle: '.move',
        axis: 'y',
      });
    } else {
      this.$pagesSidebar.find('.cs-item .move').hide();
    }

    // create the pages
    this.pages = [];
    const pageNames = Craft.uniqueArray(response.sources.map((s) => s.page));
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
      new Craft.CustomizeSourcesModal.PageSettingsModal(this, {
        triggerElement: this.$newPageBtn,
        validateName: (name) => {
          if (Craft.CustomizeSourcesModal.Page.nameId(name ?? '') === '') {
            return Craft.t('yii', '{attribute} cannot be blank.', {
              attribute: Craft.t('app', 'Page Name'),
            });
          }
          if (!this.isPageNameUnique(name)) {
            return Craft.t('app', 'Another page already has that name.');
          }
          return true;
        },
        onSave: async (name, icon) => {
          await this.addPage(name, icon, true);
        },
      });
    });
  },

  isPageNameUnique: function (name, page) {
    const nameId = Craft.CustomizeSourcesModal.Page.nameId(name);
    return !this.pages.find(
      (p) =>
        p !== page && Craft.CustomizeSourcesModal.Page.nameId(p.name) === nameId
    );
  },

  addPage: async function (name, icon = null, isNew = false) {
    const $item = $('<li class="cs-item"/>').appendTo(this.$pagesSidebarItems);
    const $itemButton = $('<div class="cs-item__btn cs-item__page-btn"/>')
      .attr({
        tabindex: '0',
        role: 'button',
      })
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
        `<a class="move icon cs-item__move" title="${Craft.t(
          'app',
          'Reorder'
        )}" role="button"></a>`
      ).appendTo($item);
    }

    $('<input/>', {
      type: 'hidden',
      name: `pageSettings[${name}][icon]`,
      value: icon || '',
      'data-icon-input': 'true',
    }).appendTo($item);

    const page = new Craft.CustomizeSourcesModal.Page(
      this,
      $item,
      $itemButton,
      name,
      icon,
      isNew
    );
    this.pageDrag?.addItems($item);

    // Select this by default?
    if (
      this.elementIndex.settings.page &&
      Craft.CustomizeSourcesModal.Page.nameId(
        this.elementIndex.settings.page
      ) === Craft.CustomizeSourcesModal.Page.nameId(name)
    ) {
      page.select();
    }

    this.pages.push(page);
    this.updatePageActionButtons();

    if (isNew) {
      Craft.cp.announce(Craft.t('app', 'Success'));
    }

    return page;
  },

  focusLabelInput: function () {
    this.selectedSource.$labelInput.focus();
  },

  getSourceName: function () {
    return this.selectedSource
      ? this.selectedSource.sourceData.label
      : this.sources[0].sourceData.label;
  },

  getSourceContainer: function (pageName, create = true) {
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
  },

  addSource: function (sourceData, isNew) {
    const pageName =
      sourceData.page ?? this.selectedPage?.name ?? '__DEFAULT__';
    sourceData.page = pageName;
    const isHeading = sourceData.type === 'heading';

    const $sourceContainer = this.getSourceContainer(pageName);
    const $item = $('<li class="cs-item"/>').appendTo($sourceContainer);
    const $itemButton = $('<div class="cs-item__btn"/>')
      .attr({
        tabindex: '0',
        role: 'button',
      })
      .append(
        $('<div/>', {
          id: `cs-item-label-${Math.floor(Math.random() * 1000000)}`,
          class: 'label',
        })
      )
      .append($('<div class="handle"/>'))
      .appendTo($item);

    // Sources pre 5.8 don't have a `key` so we need to add one if it is missing.
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
        `<a class="move icon cs-item__move" title="${Craft.t(
          'app',
          'Reorder'
        )}" role="button"></a>`
      ).appendTo($item);
    }

    let source;

    if (isHeading) {
      $item.addClass('cs-item--heading');

      /**
       * We add this here so it will get sent in every POST request.
       * This ensures that header sources will be updated to the new format
       * on save.
       * When updating, this will result in two `sources[${key}][heading]` values
       * getting sent to the server, but that should be fine.
       */
      $('<input type="hidden"/>')
        .attr('name', `sources[${sourceData.key}][heading]`)
        .val(sourceData.heading)
        .appendTo($item);

      source = new Craft.CustomizeSourcesModal.Heading(
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
        source = new Craft.CustomizeSourcesModal.Source(
          this,
          $item,
          $itemButton,
          $itemInput,
          sourceData,
          isNew
        );
      } else {
        source = new Craft.CustomizeSourcesModal.CustomSource(
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

      // Select this by default?
      if (sourceData.key === this.elementIndex.rootSourceKey) {
        source.select();
      }
    }

    this.sourceDrag?.addItems($item);

    this.sources.push(source);
    this.updateSourceActionButtons();

    if (isNew) {
      Craft.cp.announce(Craft.t('app', 'Success'));
    }

    return source;
  },

  updatePageActionButtons: function () {
    for (const page of this.pages) {
      page.updateActionButton();
    }
  },

  updateSourceActionButtons: function () {
    for (const source of this.sources) {
      source.updateActionButton();
    }
  },

  setSelectedScreen: function ($screen) {
    if (this.$body.width() >= 700) {
      return;
    }

    this.$pagesSidebar?.removeClass('cs-selected-screen');
    this.$sourcesSidebar.removeClass('cs-selected-screen');
    this.$sourceSettingsOuterContainer.removeClass('cs-selected-screen');
    $screen.addClass('cs-selected-screen');
    Craft.setFocusWithin($screen);
  },

  save: function (ev) {
    if (ev) {
      ev.preventDefault();
    }

    if (
      this.$saveBtn.hasClass('disabled') ||
      this.$saveBtn.hasClass('loading')
    ) {
      return;
    }

    this.$saveBtn.addClass('loading');

    Craft.sendActionRequest(
      'POST',
      'element-index-settings/save-customize-sources-modal-settings',
      {
        data:
          this.$container.serialize() +
          `&elementType=${this.elementIndex.elementType}`,
      }
    )
      .then(async ({data}) => {
        // Figure out which source to select
        let sourceKey = null;
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
      .catch((e) => {
        Craft.cp.displayError(e?.response?.data?.message);
      })
      .finally(() => {
        this.$saveBtn.removeClass('loading');
      });
  },

  destroy: function () {
    const sources = [...this.sources];
    for (const source of sources) {
      source.destroy();
    }

    if (this.pages) {
      const pages = [...this.pages];
      for (const page of pages) {
        page.destroy();
      }
    }

    if (this.sourceMenu) {
      this.sourceMenu.destroy();
      this.$sourceMenu.remove();
    }

    this.base();
  },
});

Craft.CustomizeSourcesModal.PageSettingsModal = Garnish.Modal.extend({
  modal: null,
  name: null,
  icon: null,

  init: function (modal, name, icon, settings) {
    // (settings)
    if (typeof name === 'object') {
      settings = name;
      name = null;
    }

    this.modal = modal;
    this.name = name;
    this.icon = icon;

    const $container = $('<form class="modal fitted"/>').appendTo(Garnish.$bod);
    const $body = $('<div class="body"/>').appendTo($container);
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
    ).appendTo($container);
    const $cancelBtn = Craft.ui
      .createButton({
        label: Craft.t('app', 'Cancel'),
      })
      .appendTo($footer);
    Craft.ui
      .createSubmitButton({
        label: Craft.t('app', 'Save'),
      })
      .appendTo($footer);

    const $nameInput = $nameField.find('.text');
    $nameInput.on('input', () => {
      this.name = $nameInput.val();
    });

    const iconPicker = $iconField.find('.icon-picker').data('iconpicker');
    iconPicker.on('change', (ev) => {
      this.icon = ev.iconName;
    });

    $cancelBtn.on('activate', () => {
      this.hide();
    });

    $container.on('submit', (ev) => {
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

    this.base($container, settings);
  },

  onFadeOut: function () {
    this.base();
    this.destroy();
  },
});

Craft.CustomizeSourcesModal.SourceDrag = Garnish.DragSort.extend({
  modal: null,
  activePage: null,

  init: function (modal, settings = {}) {
    this.modal = modal;

    settings.filter = () => {
      // If a heading is being dragged, also include the following sources
      if (this.$targetItem.hasClass('cs-item--heading')) {
        return this.$targetItem.add(
          this.$targetItem.nextUntil('.cs-item--heading')
        );
      } else {
        return this.$targetItem;
      }
    };

    if (!this.modal.multiPage) {
      settings.axis = 'y';
    }

    this.base(settings);
  },

  onDragStart: function () {
    this.activePage = null;
    this.base();
  },

  onDrag: function () {
    if (this.modal.multiPage) {
      this.onDrag._activePage = null;

      // is the cursor over any of the pages?
      for (const page of this.modal.pages) {
        if (
          page !== this.modal.selectedPage &&
          Garnish.hitTest(this.mouseX, this.mouseY, page.$item)
        ) {
          this.onDrag._activePage = page;
          break;
        }
      }

      // has the drop target changed?
      if (
        (this.activePage && this.onDrag._activePage !== this.activePage) ||
        (!this.activePage && this.onDrag._activePage !== null)
      ) {
        // was there a previous one?
        if (this.activePage) {
          this.activePage.$item.removeClass('active');
        }

        // remember the new one
        if (this.onDrag._activePage) {
          this.activePage = this.onDrag._activePage;
          this.activePage.$item.addClass('active');
        } else {
          this.activePage = null;
        }
      }
    }

    this.base();
  },

  onDragStop: function () {
    if (this.activePage) {
      this.$draggee.each((i, draggee) => {
        $(draggee).data('source').moveToPage(this.activePage);
      });
      this.$draggee.show().css('visibility', '');
      this.activePage.$item.removeClass('active');
      this.fadeOutHelpers();
    }

    this.base();
  },

  returnHelpersToDraggees: function () {
    if (this.activePage) {
      return;
    }
    this.base();
  },
});

Craft.CustomizeSourcesModal.Page = Garnish.Base.extend(
  {
    modal: null,
    actionMenu: null,

    $item: null,
    $itemButton: null,
    $actionBtn: null,
    $actionMenu: null,
    _name: null,
    _icon: null,
    isNew: null,

    moveUpBtn: null,
    moveDownBtn: null,
    removeBtn: null,

    init: function (modal, $item, $itemButton, name, icon, isNew) {
      this.modal = modal;
      this.$item = $item;
      this.$itemButton = $itemButton;
      this._name = name;
      this._icon = icon;
      this.isNew = isNew;

      this.$item.data('page', this);

      this.createActionMenu();

      this.addListener(this.$itemButton, 'activate', this.select);
    },

    createActionMenu: function () {
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
          if (prev) {
            this.$item.insertBefore(prev.$item);
          }
        },
      });

      this.moveDownBtn = this.actionMenu.addItem({
        icon: 'arrow-down',
        label: Craft.t('app', 'Move down'),
        onActivate: () => {
          const next = this.getNextPage();
          if (next) {
            this.$item.insertAfter(next.$item);
          }
        },
      });

      this.actionMenu.addItem({
        icon: 'gear',
        label: Craft.t('app', 'Page settings'),
        onActivate: () => {
          new Craft.CustomizeSourcesModal.PageSettingsModal(
            this,
            this.name,
            this.icon,
            {
              triggerElement: this.$actionBtn,
              validateName: (name) => {
                if (!this.modal.isPageNameUnique(name, this)) {
                  return Craft.t('app', 'Another page already has that name.');
                }
                return true;
              },
              onSave: (name, icon) => {
                this.name = name;
                this.icon = icon;
              },
            }
          );
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
    },

    updateActionButton: function () {
      this.actionMenu.toggleItem(this.moveUpBtn, !!this.getPrevPage());
      this.actionMenu.toggleItem(this.moveDownBtn, !!this.getNextPage());
      this.actionMenu.toggleItem(this.removeBtn, this.modal.pages.length > 1);

      if (this.actionMenu.hasVisibleItems()) {
        this.$actionBtn.removeClass('hidden');
      } else {
        this.$actionBtn.addClass('hidden');
      }
    },

    getPrevPage: function () {
      return this.$item.prev('.cs-item').data('page');
    },

    getNextPage: function () {
      return this.$item.next('.cs-item').data('page');
    },

    getSourceContainer: function (create = true) {
      return this.modal.getSourceContainer(this.name, create);
    },

    isSelected: function () {
      return this.modal.selectedPage === this;
    },

    select: function () {
      this.modal.setSelectedScreen(this.modal.$sourcesSidebar);

      if (this.isSelected()) {
        return;
      }

      if (this.modal.selectedPage) {
        this.modal.selectedPage.deselect();
      }

      this.$item.addClass('sel');
      this.$itemButton.attr({
        'aria-current': 'true',
      });

      if (this.modal.sourceContainers[this.name]) {
        this.modal.sourceContainers[this.name].removeClass('hidden');
      }

      this.modal.selectedPage = this;

      this.modal.$sourceSettingsContainer.scrollTop(0);
    },

    deselect: function () {
      this.$item.removeClass('sel');
      this.$itemButton.attr({
        'aria-current': 'false',
      });

      if (this.modal.sourceContainers[this.name]) {
        this.modal.sourceContainers[this.name].addClass('hidden');
      }

      if (this.modal.selectedSource) {
        this.modal.selectedSource.deselect();
      }

      this.modal.selectedPage = null;
    },

    set name(name) {
      if (name === this.name) {
        return;
      }

      this.$item.find('.label').text(name);
      this.$item
        .find('[data-icon-input]')
        .attr('name', `pageSettings[${name}][icon]`);

      const $sourceContainer = this.getSourceContainer(false);

      if ($sourceContainer) {
        this.modal.sourceContainers[name] = $sourceContainer;
        delete this.modal.sourceContainers[this.name];

        $sourceContainer.find('.cs-item').each((i, item) => {
          $(item).data('source').$pageInput.val(name);
        });
      }

      this._name = name;
    },

    get name() {
      return this._name;
    },

    set icon(icon) {
      if (icon === this.icon) {
        return;
      }

      this.$item.find('[data-icon-input]').val(icon || '');

      const $icon = this.$item.find('.cp-icon');

      if (icon) {
        Craft.ui.icon(icon).then((html) => {
          $icon.html(html);
        });
      } else {
        $icon.html('');
      }

      this._icon = icon;
    },

    get icon() {
      return this._icon;
    },

    destroy: function () {
      this.modal.pageDrag?.removeItems(this.$item);
      this.modal.pages = this.modal.pages.filter((p) => p !== this);

      let $closestItem = this.$item.prev('.cs-item');
      if (!$closestItem.length) {
        $closestItem = this.$item.next('.cs-item');
      }

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

      this.base();
    },
  },
  {
    nameId: (n) => n.replace(/[^\p{L}\p{N}\p{M}]/gu, '').toLowerCase(),
  }
);

Craft.CustomizeSourcesModal.BaseSource = Garnish.Base.extend({
  modal: null,
  actionMenu: null,

  $item: null,
  $itemButton: null,
  $itemInput: null,
  $pageInput: null,
  $actionBtn: null,
  $actionMenu: null,
  $settingsContainer: null,

  sourceData: null,
  isNew: null,

  moveUpBtn: null,
  moveDownBtn: null,

  init: function (modal, $item, $itemButton, $itemInput, sourceData, isNew) {
    this.modal = modal;
    this.$item = $item;
    this.$itemButton = $itemButton;
    this.$itemInput = $itemInput;
    this.sourceData = sourceData;
    this.isNew = isNew;

    this.$item.data('source', this);

    this.createActionMenu();

    if (this.modal.multiPage) {
      this.$pageInput = $('<input/>', {
        type: 'hidden',
        name: `sourcePages[${this.sourceData.key}]`,
        value: sourceData.page,
      }).appendTo(this.$item);
    }

    this.addListener(this.$itemButton, 'activate', this.select);
  },

  createActionMenu: function () {
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
        if (prev) {
          this.$item.insertBefore(prev.$item);
        }
      },
    });

    this.moveDownBtn = this.actionMenu.addItem({
      icon: 'arrow-down',
      label: Craft.t('app', 'Move down'),
      onActivate: () => {
        const next = this.getNextSource();
        if (next) {
          this.$item.insertAfter(next.$item);
        }
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
  },

  getPrevSource: function () {
    return this.$item.prev('.cs-item').data('source');
  },

  getNextSource: function () {
    return this.$item.next('.cs-item').data('source');
  },

  updateActionButton: function () {
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
      this.modal.pages.forEach((page) => {
        if (page.name !== currentPage) {
          const button = this.actionMenu.addItem(
            {
              icon: page.icon
                ? async () => await Craft.ui.icon(page.icon)
                : null,
              label: Craft.t('app', 'Move to {page}', {
                page: page.name,
              }),
            },
            $ul[0]
          );

          $(button).on('activate', () => {
            this.actionMenu.hide();
            this.moveToPage(page);
          });
        }
      });

      this.$actionMenu.find('[cs-multi-page-action]').remove();
    }

    if (this.actionMenu.hasVisibleItems()) {
      this.$actionBtn.removeClass('hidden');
    } else {
      this.$actionBtn.addClass('hidden');
    }
  },

  isHeading: function () {
    return false;
  },

  isCustomSource: function () {
    return false;
  },

  isSelected: function () {
    return this.modal.selectedSource === this;
  },

  select: function () {
    this.modal.setSelectedScreen(this.modal.$sourceSettingsOuterContainer);

    if (this.isSelected()) {
      return;
    }

    if (this.modal.selectedSource) {
      this.modal.selectedSource.deselect();
    }

    this.$item.addClass('sel');
    this.$itemButton.attr({
      'aria-current': 'true',
    });
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
  },

  createSettings: async function () {},

  getIndexSourceItem: function () {},

  deselect: function () {
    this.$item.removeClass('sel');
    this.$itemButton.attr({
      'aria-current': 'false',
    });
    this.modal.selectedSource = null;
    this.$settingsContainer.addClass('hidden');
  },

  updateItemLabel: function (val) {
    if (val) {
      this.$itemButton.find('.label').text(val);
    } else {
      this.$itemButton.find('.label').html('&nbsp;');
    }
  },

  updateItemHandle: function (val) {
    if (val) {
      this.$itemButton.find('.handle').text(val);
    } else {
      this.$itemButton.find('.handle').empty();
    }
  },

  moveToPage: function (page) {
    this.$item.appendTo(this.modal.getSourceContainer(page.name));
    this.$pageInput.val(page.name);

    if (this.isSelected()) {
      this.deselect();
    }

    this.modal.updateSourceActionButtons();
  },

  destroy: function () {
    this.modal.sourceDrag?.removeItems(this.$item);
    this.modal.sources = this.modal.sources.filter((s) => s !== this);

    if (this.isSelected()) {
      this.deselect();

      let $closestItem = this.$item.prev('.cs-item');
      if (!$closestItem.length) {
        $closestItem = this.$item.next('.cs-item');
      }
      if ($closestItem.length) {
        $closestItem.data('source').select();
      }

      Garnish.setFocusWithin(this.modal.$sourceSettingsContainer);
    }

    this.$item.data('source', null);
    this.$item.remove();

    this.actionMenu.destroy();
    this.$actionMenu.remove();

    if (this.$settingsContainer) {
      this.$settingsContainer.remove();
    }

    this.modal.updateSourceActionButtons();

    this.base();
  },
});

Craft.CustomizeSourcesModal.Source =
  Craft.CustomizeSourcesModal.BaseSource.extend({
    $viewModeInput: null,
    viewModeListbox: null,

    $sortAttributeSelect: null,
    $sortDirectionPicker: null,
    $sortDirectionInput: null,
    sortDirectionListbox: null,

    createSettings: async function ($container) {
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
    },

    createViewModeField: function ($container) {
      const $inputContainer = $('<section/>', {
        class: 'btngroup btngroup--exclusive',
        'aria-label': Craft.t('app', 'View mode options'),
      });

      const viewModes = this.modal.viewModes.filter(
        (viewMode) => !viewMode.structuresOnly || this.sourceData.structureId
      );
      let defaultViewMode = this.sourceData.defaultViewMode;
      if (
        !defaultViewMode ||
        !viewModes.some((viewMode) => viewMode.mode === defaultViewMode)
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
        $('<div/>', {
          class: 'cp-icon small',
        })
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
        onChange: ($selectedOption) => {
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
    },

    createSortField: function ($container) {
      const $inputContainer = $('<div class="flex"/>');

      const options = this.sourceData.sortOptions.sort((a, b) => {
        return a.label === b.label ? 0 : a.label < b.label ? -1 : 1;
      });
      const groups = options.reduce(
        (groups, o) => {
          let key;
          if (o.attr === 'structure') {
            groups.structure.push(o);
          } else if (o.attr.startsWith('field:')) {
            groups.field.push(o);
          } else {
            groups.attribute.push(o);
          }
          return groups;
        },
        {
          structure: [],
          attribute: [],
          field: [],
        }
      );
      if (groups.field.length) {
        groups.field.unshift({
          optgroup: Craft.t('app', 'Fields'),
        });
      }

      const $sortAttributeSelectContainer = Craft.ui
        .createSelect({
          name: `sources[${this.sourceData.key}][defaultSort][0]`,
          options: [
            ...groups.structure,
            ...groups.attribute,
            ...groups.field,
          ].map((o) => {
            return o.optgroup
              ? o
              : {
                  label: o.label,
                  value: o.attr,
                };
          }),
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
        onChange: ($selectedOption) => {
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
    },

    handleSortAttributeChange: function (useDefaultDir) {
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
          : this.sourceData.sortOptions.find((o) => o.attr === attr).defaultDir;
        this.sortDirectionListbox.select(dir === 'asc' ? 0 : 1);
      }
    },

    createTableAttributesField: function ($container) {
      const availableTableAttributes = this.availableTableAttributes().sort(
        (a, b) => {
          return a[1] === b[1] ? 0 : a[1] < b[1] ? -1 : 1;
        }
      );

      if (
        !this.sourceData.tableAttributes.length &&
        !availableTableAttributes.length
      ) {
        return;
      }

      const name = `sources[${this.sourceData.key}][tableAttributes][]`;

      $('<input/>', {
        type: 'hidden',
        name,
        value: '',
      }).appendTo($container);

      Craft.ui
        .createSortableCheckboxSelectField({
          label: Craft.t('app', 'Default Table Columns'),
          instructions: Craft.t(
            'app',
            'Choose which table columns should be visible for this source by default.'
          ),
          name,
          options: availableTableAttributes.map(([key, label]) => ({
            label,
            value: key,
          })),
          values: this.sourceData.tableAttributes.map(([key]) => key),
        })
        .appendTo($container);
    },

    availableTableAttributes: function () {
      const attributes = this.modal.availableTableAttributes.slice(0);
      attributes.push(...this.sourceData.availableTableAttributes);
      return attributes;
    },

    getIndexSourceItem: function () {
      const $source = this.modal.elementIndex.getSourceByKey(
        this.sourceData.key
      );

      if ($source) {
        return $source.closest('li');
      }
    },
  });

Craft.CustomizeSourcesModal.CustomSource =
  Craft.CustomizeSourcesModal.Source.extend({
    $labelInput: null,

    isCustomSource: function () {
      return true;
    },

    createSettings: async function ($container) {
      const $labelField = Craft.ui
        .createTextField({
          label: Craft.t('app', 'Label'),
          name: `sources[${this.sourceData.key}][label]`,
          value: this.sourceData.label,
        })
        .appendTo($container);
      this.$labelInput = $labelField.find('.text');
      const defaultId = `condition${Math.floor(Math.random() * 1000000)}`;

      const swapPlaceholders = (str) =>
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
            options: Craft.sites.map((site) => ({
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

      this.addListener(this.$labelInput, 'input', 'handleLabelInputChange');
    },

    availableTableAttributes: function () {
      const attributes = this.base();
      if (this.isNew) {
        let existingFieldAttributes = [];
        let customFieldAttributes = [];
        this.modal.customFieldAttributes.forEach((item) => {
          if (existingFieldAttributes.indexOf(item[0]) == -1) {
            existingFieldAttributes.push(item[0]);
            customFieldAttributes.push(item);
          }
        });
        attributes.push(...customFieldAttributes);
      }
      return attributes;
    },

    handleLabelInputChange: function () {
      this.updateItemLabel(this.$labelInput.val());
    },

    getIndexSourceItem: function () {
      let $source = this.base();
      let $label;

      if ($source) {
        $label = $source.find('.label');
      } else {
        $label = $('<span/>', {class: 'label'});
        $source = $('<li/>').append(
          $('<a/>', {
            'data-key': this.sourceData.key,
          }).append($label)
        );
      }

      if (this.$labelInput) {
        let label = this.$labelInput.val().trim();
        if (label === '') {
          label = Craft.t('app', '(blank)');
        }
        $label.text(label);
      }

      return $source;
    },
  });

Craft.CustomizeSourcesModal.Heading =
  Craft.CustomizeSourcesModal.BaseSource.extend({
    $labelInput: null,

    isHeading: function () {
      return true;
    },

    createSettings: async function ($container) {
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

      this.addListener(this.$labelInput, 'input', 'handleLabelInputChange');
    },

    handleLabelInputChange: function () {
      this.updateItemLabel(this.$labelInput.val());
    },

    updateItemLabel: function (val) {
      this.$itemButton
        .find('.label')
        .html(
          (val
            ? Craft.escapeHtml(val)
            : `<em>${Craft.t('app', '(blank)')}</em>`) + '&nbsp;'
        );
    },

    getIndexSourceItem: function () {
      const label =
        (this.$labelInput ? this.$labelInput.val() : null) ||
        this.sourceData.heading ||
        '';
      return $('<li class="heading"/>')
        .append($('<span/>').text(label))
        .append('<ul/>');
    },
  });
