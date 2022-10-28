/** global: Craft */
/** global: Garnish */
/**
 * Customize Sources modal
 */
Craft.CustomizeSourcesModal = Garnish.Modal.extend({
  elementIndex: null,
  $elementIndexSourcesContainer: null,

  $sidebar: null,
  $sidebarToggleBtn: null,
  $sourcesContainer: null,
  $sourcesHeader: null,
  $sourcesHeading: null,
  $sourceSettingsContainer: null,
  $sourceSettingsHeader: null,
  $addSourceMenu: null,
  addSourceMenu: null,
  $footer: null,
  $footerBtnContainer: null,
  $saveBtn: null,
  $cancelBtn: null,
  $loadingSpinner: null,

  sourceSort: null,
  sources: null,
  selectedSource: null,

  elementTypeName: null,
  baseSortOptions: null,
  availableTableAttributes: null,
  customFieldAttributes: null,

  conditionBuilderHtml: null,
  conditionBuilderJs: null,
  userGroups: null,

  init: function (elementIndex, settings) {
    this.base();

    this.setSettings(settings, {
      resizable: true,
    });

    this.elementIndex = elementIndex;
    this.$elementIndexSourcesContainer = this.elementIndex.$sidebar
      .children('nav')
      .children('ul');

    const $container = $(
      '<form class="modal customize-sources-modal"/>'
    ).appendTo(Garnish.$bod);

    this.$sidebar = $('<div class="cs-sidebar block-types"/>').appendTo(
      $container
    );
    this.$sourcesContainer = $('<div class="sources">').appendTo(this.$sidebar);
    this.$sourceSettingsContainer = $('<div class="source-settings">').appendTo(
      $container
    );

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
      .then((response) => {
        this.$saveBtn.removeClass('disabled');
        this.buildModal(response.data);
      })
      .finally(() => {
        this.$loadingSpinner.remove();
      });

    this.addListener(this.$cancelBtn, 'click', 'hide');
    this.addListener(this.$saveBtn, 'click', 'save');
    this.addListener(this.$container, 'submit', 'save');
  },

  buildModal: function (response) {
    this.baseSortOptions = response.baseSortOptions;
    this.defaultSortOptions = response.defaultSortOptions;
    this.availableTableAttributes = response.availableTableAttributes;
    this.customFieldAttributes = response.customFieldAttributes;
    this.elementTypeName = response.elementTypeName;
    this.conditionBuilderHtml = response.conditionBuilderHtml;
    this.conditionBuilderJs = response.conditionBuilderJs;
    this.userGroups = response.userGroups;

    if (response.headHtml) {
      Craft.appendHeadHtml(response.headHtml);
    }
    if (response.bodyHtml) {
      Craft.appendBodyHtml(response.bodyHtml);
    }

    // Create the source item sorter
    this.sourceSort = new Garnish.DragSort({
      handle: '.move',
      axis: 'y',
    });

    // Create the sources
    this.sources = [];

    for (let i = 0; i < response.sources.length; i++) {
      this.sources.push(this.addSource(response.sources[i]));
    }

    if (!this.selectedSource && typeof this.sources[0] !== 'undefined') {
      this.sources[0].select();
    }

    const $menuBtnContainer = $(
      '<div class="buttons left" data-wrapper/>'
    ).appendTo(this.$footer);
    const $menuBtn = $('<button/>', {
      type: 'button',
      class: 'btn menubtn add icon',
      'aria-label': Craft.t('app', 'Add…'),
      'aria-controls': 'add-source-menu',
      title: Craft.t('app', 'Add…'),
      'data-disclosure-trigger': '',
    }).appendTo($menuBtnContainer);

    this.$addSourceMenu = $('<div/>', {
      id: 'add-source-menu',
      class: 'menu menu--disclosure',
    }).appendTo($menuBtnContainer);

    const addSource = (sourceData) => {
      const source = this.addSource(sourceData, true);
      Garnish.scrollContainerToElement(this.$sidebar, source.$item);
      source.select();
      this.addSourceMenu.hide();
    };

    const $newHeadingBtn = $('<button/>', {
      type: 'button',
      class: 'menu-option',
      text: Craft.t('app', 'New heading'),
    }).on('click', () => {
      addSource({
        type: 'heading',
      });
    });

    const $newCustomSourceBtn = $('<button/>', {
      type: 'button',
      class: 'menu-option',
      text: Craft.t('app', 'New custom source'),
      'data-type': 'custom',
    }).on('click', () => {
      const sortOptions = this.baseSortOptions.slice(0);
      sortOptions.push(this.defaultSortOptions);

      addSource({
        type: 'custom',
        key: `custom:${Craft.uuid()}`,
        sortOptions: sortOptions,
        defaultSort: [sortOptions[0].attr, sortOptions[1].defaultDir],
        tableAttributes: [],
        availableTableAttributes: [],
      });
    });

    const $ul = $('<ul/>')
      .append($('<li/>').append($newHeadingBtn))
      .appendTo(this.$addSourceMenu);

    if (response.conditionBuilderHtml) {
      $('<li/>').append($newCustomSourceBtn).appendTo($ul);
    }

    if (Craft.useMobileStyles()) {
      this.buildSidebarToggleView();
    }

    // Add resize listener to enable/disable sidebar toggle view
    this.addListener(Garnish.$win, 'resize', this.updateSidebarView);

    this.addSourceMenu = new Garnish.DisclosureMenu($menuBtn);
  },

  getSourceName: function () {
    return this.selectedSource
      ? this.selectedSource.sourceData.label
      : this.sources[0].sourceData.label;
  },

  updateSidebarView: function () {
    if (Craft.useMobileStyles()) {
      if (!this.$sidebarToggleBtn) this.buildSidebarToggleView();
    } else {
      if (this.$sidebarToggleBtn) this.resetView();
    }
  },

  resetView: function () {
    if (this.$sourceSettingsHeader) {
      this.$sourceSettingsHeader.remove();
    }

    if (this.$sourcesHeader) {
      this.$sourcesHeader.remove();
    }

    this.$sidebarToggleBtn = null;
    this.$container.removeClass('sidebar-hidden');
  },

  updateHeading: function () {
    if (!this.$sourcesHeading) return;

    this.$sourcesHeading.text(this.getSourceName());
  },

  buildSidebarToggleView: function () {
    this.$sourcesHeader = $('<div class="sources-header"/>')
      .addClass('sidebar-header')
      .prependTo(this.$sourcesContainer);

    this.$sidebarCloseBtn = Craft.ui
      .createButton({
        class: 'nav-close close-btn',
      })
      .attr('aria-label', Craft.t('app', 'Close'))
      .removeClass('btn')
      .appendTo(this.$sourcesHeader);

    this.$sourcesHeading = $('<h1 class="main-heading"/>').text(
      this.getSourceName()
    );

    this.$sourceSettingsHeader = $('<div class="source-settings-header"/>')
      .addClass('main-header')
      .append(this.$sourcesHeading)
      .prependTo(this.$sourceSettingsContainer);

    // Toggle sidebar button
    const buttonConfig = {
      toggle: true,
      controls: 'modal-sidebar',
      class: 'nav-toggle',
    };

    this.$sidebarToggleBtn = Craft.ui
      .createButton(buttonConfig)
      .removeClass('btn')
      .attr('aria-label', Craft.t('app', 'Show sidebar'))
      .appendTo(this.$sourceSettingsHeader);

    this.closeSidebar();

    // Add listeners
    this.addListener(this.$sidebarToggleBtn, 'click', () => {
      this.toggleSidebar();
    });

    this.addListener(this.$sidebarCloseBtn, 'click', () => {
      this.toggleSidebar();
      this.$sidebarToggleBtn.trigger('focus');
    });
  },

  toggleSidebar: function () {
    if (this.sidebarIsOpen()) {
      this.closeSidebar();
    } else {
      this.openSidebar();
    }
  },

  openSidebar: function () {
    this.$container.removeClass('sidebar-hidden');
    this.$sidebarToggleBtn.attr('aria-expanded', 'true');
    this.$sidebar.find(':focusable').first().focus();

    Garnish.uiLayerManager.addLayer(this.$sidebar);

    Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, () => {
      this.closeSidebar();

      if (Garnish.focusIsInside(this.$sidebar)) {
        this.$sidebarToggleBtn.focus();
      }
    });
  },

  closeSidebar: function () {
    this.$container.addClass('sidebar-hidden');

    if (this.$sidebarToggleBtn) {
      this.$sidebarToggleBtn.attr('aria-expanded', 'false');
    }

    // if sidebar is topmost layer, remove layer
    if (Garnish.uiLayerManager.currentLayer.$container.hasClass('cs-sidebar')) {
      Garnish.uiLayerManager.removeLayer();
    }
  },

  sidebarIsOpen: function () {
    return this.$sidebarToggleBtn.attr('aria-expanded') === 'true';
  },

  addSource: function (sourceData, isNew) {
    const $item = $('<div class="customize-sources-item"/>').appendTo(
      this.$sourcesContainer
    );
    const $itemLabel = $('<div class="label customize-sources-item__btn"/>')
      .attr({
        tabindex: '0',
        role: 'button',
      })
      .appendTo($item);
    const $itemInput = $('<input type="hidden"/>').appendTo($item);
    $(
      `<a class="move icon customize-sources-item__move" title="${Craft.t(
        'app',
        'Reorder'
      )}" role="button"></a>`
    ).appendTo($item);

    let source;

    if (sourceData.type === 'heading') {
      $item.addClass('heading');
      $itemInput.attr('name', 'sourceOrder[][heading]');
      source = new Craft.CustomizeSourcesModal.Heading(
        this,
        $item,
        $itemLabel,
        $itemInput,
        sourceData,
        isNew
      );
      source.updateItemLabel(sourceData.heading);
    } else {
      $itemInput.attr('name', 'sourceOrder[][key]').val(sourceData.key);
      if (sourceData.type === 'native') {
        source = new Craft.CustomizeSourcesModal.Source(
          this,
          $item,
          $itemLabel,
          $itemInput,
          sourceData,
          isNew
        );
      } else {
        source = new Craft.CustomizeSourcesModal.CustomSource(
          this,
          $item,
          $itemLabel,
          $itemInput,
          sourceData,
          isNew
        );
      }
      source.updateItemLabel(sourceData.label);

      // Select this by default?
      if (sourceData.key === this.elementIndex.rootSourceKey) {
        source.select();
      }
    }

    this.sourceSort.addItems($item);
    return source;
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
      .then(({data}) => {
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
          this.elementIndex.selectSourceByKey(sourceKey);
        }

        window.location.reload();
      })
      .catch(() => {
        Craft.cp.displayError(Craft.t('app', 'A server error occurred.'));
      })
      .finally(() => {
        this.$saveBtn.removeClass('loading');
      });
  },

  appendIndexSourceItem: function ($sourceItem, $lastSourceItem) {
    if (!$lastSourceItem) {
      $sourceItem.prependTo(this.$elementIndexSourcesContainer);
    } else {
      const isHeading = $sourceItem.hasClass('heading');
      if ($lastSourceItem.hasClass('heading') && !isHeading) {
        // First source to be placed below a new heading
        $sourceItem.appendTo($lastSourceItem.children('ul'));
      } else {
        if (isHeading) {
          // New heading. Swap $lastSourceItem with the top level <li> if it's nested
          const $lastTopLevelSource = $lastSourceItem
            .parentsUntil(this.$elementIndexSourcesContainer, 'li')
            .last();
          if ($lastTopLevelSource.length) {
            $lastSourceItem = $lastTopLevelSource;
          }
        }
        $sourceItem.insertAfter($lastSourceItem);
      }
    }
  },

  destroy: function () {
    for (let i = 0; i < this.sources.length; i++) {
      this.sources[i].destroy();
    }

    if (this.addSourceMenu) {
      this.addSourceMenu.destroy();
      this.$addSourceMenu.remove();
    }

    delete this.sources;
    this.base();
  },
});

Craft.CustomizeSourcesModal.BaseSource = Garnish.Base.extend({
  modal: null,

  $item: null,
  $itemLabel: null,
  $itemInput: null,
  $settingsContainer: null,

  sourceData: null,
  isNew: null,

  init: function (modal, $item, $itemLabel, $itemInput, sourceData, isNew) {
    this.modal = modal;
    this.$item = $item;
    this.$itemLabel = $itemLabel;
    this.$itemInput = $itemInput;
    this.sourceData = sourceData;
    this.isNew = isNew;

    this.$item.data('source', this);

    this.addListener(this.$itemLabel, 'click', 'select');
    this.addListener(this.$itemLabel, 'keypress', (event) => {
      const key = event.keyCode;

      if (key === Garnish.RETURN_KEY || key === Garnish.SPACE_KEY) {
        this.select();
      }
    });
  },

  isHeading: function () {
    return false;
  },

  isNative: function () {
    return false;
  },

  isSelected: function () {
    return this.modal.selectedSource === this;
  },

  select: function () {
    if (this.isSelected()) {
      return;
    }

    if (this.modal.selectedSource) {
      this.modal.selectedSource.deselect();
    }

    this.$item.addClass('sel');
    this.$itemLabel.attr({
      'aria-current': 'true',
    });
    this.modal.selectedSource = this;
    this.modal.updateHeading();

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

  createSettings: function () {},

  getIndexSourceItem: function () {},

  deselect: function () {
    this.$item.removeClass('sel');
    this.$itemLabel.attr({
      'aria-current': 'false',
    });
    this.modal.selectedSource = null;
    this.$settingsContainer.addClass('hidden');
  },

  updateItemLabel: function (val) {
    if (val) {
      this.$itemLabel.text(val);
    } else {
      this.$itemLabel.html('&nbsp;');
    }
  },

  destroy: function () {
    this.modal.sourceSort.removeItems(this.$item);
    this.modal.sources.splice($.inArray(this, this.modal.sources), 1);

    if (this.isSelected()) {
      this.deselect();

      if (this.modal.sources.length) {
        this.modal.sources[0].select();
      }
    }

    this.$item.data('source', null);
    this.$item.remove();

    if (this.$settingsContainer) {
      this.$settingsContainer.remove();
    }

    this.base();
  },
});

Craft.CustomizeSourcesModal.Source =
  Craft.CustomizeSourcesModal.BaseSource.extend({
    $sortAttributeSelect: null,
    $sortDirectionPicker: null,
    $sortDirectionInput: null,
    sortDirectionListbox: null,

    isNative: function () {
      return true;
    },

    createSettings: function ($container) {
      Craft.ui
        .createLightswitchField({
          label: Craft.t('app', 'Enabled'),
          name: `sources[${this.sourceData.key}][enabled]`,
          on: !this.sourceData.disabled,
        })
        .appendTo($container);
      this.createSortField($container);
      this.createTableAttributesField($container);
    },

    createSortField: function ($container) {
      const $inputContainer = $('<div class="flex"/>');

      const $sortAttributeSelectContainer = Craft.ui
        .createSelect({
          name: `sources[${this.sourceData.key}][defaultSort][0]`,
          options: this.sourceData.sortOptions.map((o) => {
            return {
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
      const availableTableAttributes = this.availableTableAttributes();

      if (
        !this.sourceData.tableAttributes.length &&
        !availableTableAttributes.length
      ) {
        return;
      }

      const $columnCheckboxes = $('<div/>');
      const selectedAttributes = [];

      $(
        `<input type="hidden" name="sources[${this.sourceData.key}][tableAttributes][]" value=""/>`
      ).appendTo($columnCheckboxes);

      // Add the selected columns, in the selected order
      for (let i = 0; i < this.sourceData.tableAttributes.length; i++) {
        let [key, label] = this.sourceData.tableAttributes[i];
        $columnCheckboxes.append(
          this.createTableColumnOption(key, label, true)
        );
        selectedAttributes.push(key);
      }

      // Add the rest
      for (let i = 0; i < availableTableAttributes.length; i++) {
        const [key, label] = availableTableAttributes[i];
        if (!Craft.inArray(key, selectedAttributes)) {
          $columnCheckboxes.append(
            this.createTableColumnOption(key, label, false)
          );
        }
      }

      new Garnish.DragSort($columnCheckboxes.children(), {
        handle: '.move',
        axis: 'y',
      });

      Craft.ui
        .createField($columnCheckboxes, {
          label: Craft.t('app', 'Default Table Columns'),
          instructions: Craft.t(
            'app',
            'Choose which table columns should be visible for this source by default.'
          ),
        })
        .appendTo($container);
    },

    availableTableAttributes: function () {
      const attributes = this.modal.availableTableAttributes.slice(0);
      attributes.push(...this.sourceData.availableTableAttributes);
      return attributes;
    },

    createTableColumnOption: function (key, label, checked) {
      return $('<div class="customize-sources-table-column"/>')
        .append('<div class="icon move"/>')
        .append(
          Craft.ui.createCheckbox({
            label: Craft.escapeHtml(label),
            name: `sources[${this.sourceData.key}][tableAttributes][]`,
            value: key,
            checked: checked,
          })
        );
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

    createSettings: function ($container) {
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
      Craft.appendBodyHtml(conditionBuilderJs);

      this.createSortField($container);
      this.createTableAttributesField($container);

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

      $container.append('<hr/>');

      this.$deleteBtn = $('<a class="error delete"/>')
        .text(Craft.t('app', 'Delete custom source'))
        .appendTo($container);

      this.addListener(this.$labelInput, 'input', 'handleLabelInputChange');
      this.addListener(this.$deleteBtn, 'click', 'destroy');
    },

    availableTableAttributes: function () {
      const attributes = this.base();
      if (this.isNew) {
        attributes.push(...this.modal.customFieldAttributes);
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
        let label = Craft.trim(this.$labelInput.val());
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
    $deleteBtn: null,

    isHeading: function () {
      return true;
    },

    createSettings: function ($container) {
      const $labelField = Craft.ui
        .createTextField({
          label: Craft.t('app', 'Heading'),
          instructions: Craft.t(
            'app',
            'This can be left blank if you just want an unlabeled separator.'
          ),
          value: this.sourceData.heading || '',
        })
        .appendTo($container);
      this.$labelInput = $labelField.find('.text');

      $container.append('<hr/>');

      this.$deleteBtn = $('<a class="error delete"/>')
        .text(Craft.t('app', 'Delete heading'))
        .appendTo($container);

      this.addListener(this.$labelInput, 'input', 'handleLabelInputChange');
      this.addListener(this.$deleteBtn, 'click', 'destroy');
    },

    handleLabelInputChange: function () {
      this.updateItemLabel(this.$labelInput.val());
    },

    updateItemLabel: function (val) {
      this.$itemLabel.html(
        (val
          ? Craft.escapeHtml(val)
          : `<em class="light">${Craft.t('app', '(blank)')}</em>`) + '&nbsp;'
      );
      this.$itemInput.val(val);
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
