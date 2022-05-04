/** global: Craft */
/** global: Garnish */
/**
 * Customize Sources modal
 */
Craft.CustomizeSourcesModal = Garnish.Modal.extend({
  elementIndex: null,
  $elementIndexSourcesContainer: null,

  $sidebar: null,
  $sourcesContainer: null,
  $sourceSettingsContainer: null,
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
      addSource({
        type: 'custom',
        key: `custom:${Craft.uuid()}`,
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

    this.addSourceMenu = new Garnish.DisclosureMenu($menuBtn);
  },

  addSource: function (sourceData, isNew) {
    const $item = $('<div class="customize-sources-item"/>').appendTo(
      this.$sourcesContainer
    );
    const $itemLabel = $('<div class="label"/>').appendTo($item);
    const $itemInput = $('<input type="hidden"/>').appendTo($item);
    $(
      '<a class="move icon" title="' +
        Craft.t('app', 'Reorder') +
        '" role="button"></a>'
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
      if (
        (this.elementIndex.sourceKey + '/').substring(
          0,
          sourceData.key.length + 1
        ) ===
        sourceData.key + '/'
      ) {
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
          '&elementType=' +
          this.elementIndex.elementType,
      }
    )
      .then(({data}) => {
        if (this.$elementIndexSourcesContainer.length) {
          let $lastSourceItem = null,
            $pendingHeading;

          for (let i = 0; i < this.sourceSort.$items.length; i++) {
            const $item = this.sourceSort.$items.eq(i),
              source = $item.data('source'),
              $indexSourceItem = source.getIndexSourceItem();

            if (!$indexSourceItem) {
              continue;
            }

            if (source.isHeading()) {
              $pendingHeading = $indexSourceItem;
              continue;
            }

            const $a = $indexSourceItem.children('a');
            let visible = true;

            if (source.isNative()) {
              const key = $a.data('key');
              visible = !key || !data.disabledSourceKeys.includes(key);
              if (visible) {
                $a.removeAttr('data-disabled');
              } else {
                $a.attr('data-disabled', '');
              }
            }

            if (visible && $pendingHeading) {
              this.appendIndexSourceItem($pendingHeading, $lastSourceItem);
              $lastSourceItem = $pendingHeading;
              $pendingHeading = null;
            }

            const isNew = !$indexSourceItem.parent().length;
            this.appendIndexSourceItem($indexSourceItem, $lastSourceItem);
            if (isNew) {
              this.elementIndex.initSource($a);
            }
            $lastSourceItem = $indexSourceItem;
          }

          // Remove any additional sources (most likely just old headings)
          if ($lastSourceItem) {
            const $extraSources = $lastSourceItem.nextAll();
            this.elementIndex.sourceSelect.removeItems($extraSources);
            $extraSources.remove();
          }
        }

        // Update source visibility based on updated data-disabled attributes
        this.elementIndex.updateSourceVisibility();

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

        this.elementIndex.updateElements();

        Craft.cp.displayNotice(Craft.t('app', 'Source settings saved'));
        this.hide();
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
      $sourceItem.insertAfter($lastSourceItem);
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

    this.addListener(this.$item, 'click', 'select');
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

  createSettings: function () {},

  getIndexSourceItem: function () {},

  deselect: function () {
    this.$item.removeClass('sel');
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
      this.createTableAttributesField($container);
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
          label: Craft.t('app', 'Table Columns'),
          instructions: Craft.t(
            'app',
            'Choose which table columns should be visible for this source, and in which order.'
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
            name: 'sources[' + this.sourceData.key + '][tableAttributes][]',
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

    select: function () {
      this.base();
      this.$labelInput.focus();
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

    select: function () {
      this.base();
      this.$labelInput.focus();
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
          : '<em class="light">' + Craft.t('app', '(blank)') + '</em>') +
          '&nbsp;'
      );
      this.$itemInput.val(val);
    },

    getIndexSourceItem: function () {
      const label =
        (this.$labelInput ? this.$labelInput.val() : null) ||
        this.sourceData.heading ||
        '';
      return $('<li class="heading"/>').append($('<span/>').text(label));
    },
  });
