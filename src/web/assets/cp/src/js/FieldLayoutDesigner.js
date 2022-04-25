/** global: Craft */
/** global: Garnish */
Craft.FieldLayoutDesigner = Garnish.Base.extend(
  {
    $container: null,
    $configInput: null,
    $tabContainer: null,
    $newTabBtn: null,
    $sidebar: null,
    $libraryToggle: null,
    $selectedLibrary: null,
    $fieldLibrary: null,
    $uiLibrary: null,
    $uiLibraryElements: null,
    $fieldSearch: null,
    $clearFieldSearchBtn: null,
    $fieldGroups: null,
    $fields: null,

    tabGrid: null,
    elementDrag: null,

    _config: null,

    init: function (container, settings) {
      this.$container = $(container);
      this.setSettings(settings, Craft.FieldLayoutDesigner.defaults);

      this.$configInput = this.$container.children('input[data-config-input]');
      this._config = JSON.parse(this.$configInput.val());
      if (!this._config.tabs) {
        this._config.tabs = [];
      }

      let $workspace = this.$container.children('.fld-workspace');
      this.$tabContainer = $workspace.children('.fld-tabs');
      this.$newTabBtn = $workspace.children('.fld-new-tab-btn');
      this.$sidebar = this.$container.children('.fld-sidebar');

      this.$fieldLibrary = this.$selectedLibrary =
        this.$sidebar.children('.fld-field-library');
      let $fieldSearchContainer = this.$fieldLibrary.children('.search');
      this.$fieldSearch = $fieldSearchContainer.children('input');
      this.$clearFieldSearchBtn = $fieldSearchContainer.children('.clear');
      this.$fieldGroups = this.$sidebar.find('.fld-field-group');
      this.$fields = this.$fieldGroups.children('.fld-element');
      this.$uiLibrary = this.$sidebar.children('.fld-ui-library');
      this.$uiLibraryElements = this.$uiLibrary.children();

      // Set up the layout grids
      this.tabGrid = new Craft.Grid(this.$tabContainer, {
        itemSelector: '.fld-tab',
        minColWidth: 24 * 11,
        fillMode: 'grid',
        snapToGrid: 24,
      });

      let $tabs = this.$tabContainer.children();
      for (let i = 0; i < $tabs.length; i++) {
        this.initTab($($tabs[i]));
      }

      this.elementDrag = new Craft.FieldLayoutDesigner.ElementDrag(this);

      if (this.settings.customizableTabs) {
        this.tabDrag = new Craft.FieldLayoutDesigner.TabDrag(this);

        this.addListener(this.$newTabBtn, 'activate', 'addTab');
      }

      // Set up the sidebar
      if (this.settings.customizableUi) {
        let $libraryPicker = this.$sidebar.children('.btngroup');
        new Craft.Listbox($libraryPicker, {
          onChange: ($selectedOption) => {
            this.$selectedLibrary.addClass('hidden');
            this.$selectedLibrary =
              this[`$${$selectedOption.data('library')}Library`].removeClass(
                'hidden'
              );
          },
        });
      }

      this.addListener(this.$fieldSearch, 'input', () => {
        let val = this.$fieldSearch.val().toLowerCase().replace(/['"]/g, '');
        if (!val) {
          this.$fieldLibrary.find('.filtered').removeClass('filtered');
          this.$clearFieldSearchBtn.addClass('hidden');
          return;
        }

        this.$clearFieldSearchBtn.removeClass('hidden');
        let $matches = this.$fields
          .filter(`[data-keywords*="${val}"]`)
          .add(
            this.$fieldGroups
              .filter(`[data-name*="${val}"]`)
              .children('.fld-element')
          )
          .removeClass('filtered');
        this.$fields.not($matches).addClass('filtered');

        // hide any groups that don't have any results
        for (let i = 0; i < this.$fieldGroups.length; i++) {
          let $group = this.$fieldGroups.eq(i);
          if ($group.find('.fld-element:not(.hidden):not(.filtered)').length) {
            $group.removeClass('filtered');
          } else {
            $group.addClass('filtered');
          }
        }
      });

      this.addListener(this.$fieldSearch, 'keydown', (ev) => {
        if (ev.keyCode === Garnish.ESC_KEY) {
          this.$fieldSearch.val('').trigger('input');
        }
      });

      // Clear the search when the X button is clicked
      this.addListener(this.$clearFieldSearchBtn, 'click', () => {
        this.$fieldSearch.val('').trigger('input');
      });
    },

    initTab: function ($tab) {
      return new Craft.FieldLayoutDesigner.Tab(this, $tab);
    },

    removeFieldByHandle: function (attribute) {
      this.$fields
        .filter(`[data-attribute="${attribute}"]:first`)
        .removeClass('hidden')
        .closest('.fld-field-group')
        .removeClass('hidden');
    },

    addTab: function () {
      if (!this.settings.customizableTabs) {
        return;
      }

      const name = Craft.escapeHtml(
        prompt(Craft.t('app', 'Give your tab a name.'))
      );

      if (!name) {
        return;
      }

      const $tab = $(`
<div class="fld-tab">
  <div class="tabs">
    <div class="tab sel draggable">
      <span>${name}</span>
      <a class="settings icon" title="${Craft.t('app', 'Settings')}"></a>
    </div>
  </div>
  <div class="fld-tabcontent"></div>
</div>
`).appendTo(this.$tabContainer);

      this.tabGrid.addItems($tab);
      this.tabDrag.addItems($tab);

      const tab = this.initTab($tab);
      tab.updatePositionInConfig();
    },

    get config() {
      return this._config;
    },

    set config(config) {
      this._config = config;
      this.$configInput.val(JSON.stringify(config));
    },

    updateConfig: function (callback) {
      const config = callback(this.config);
      if (config !== false) {
        this.config = config;
      }
    },
  },
  {
    defaults: {
      customizableTabs: true,
      customizableUi: true,
    },

    createSlideout: function (contents, js) {
      const $body = $('<div/>', {class: 'fld-element-settings-body'});
      $('<div/>', {class: 'fields', html: contents}).appendTo($body);
      const $footer = $('<div/>', {class: 'fld-element-settings-footer'});
      $('<div/>', {class: 'flex-grow'}).appendTo($footer);
      const $cancelBtn = Craft.ui
        .createButton({
          label: Craft.t('app', 'Close'),
          spinner: true,
        })
        .appendTo($footer);
      Craft.ui
        .createSubmitButton({
          class: 'secondary',
          label: Craft.t('app', 'Apply'),
          spinner: true,
        })
        .appendTo($footer);
      const $contents = $body.add($footer);

      const slideout = new Craft.Slideout($contents, {
        containerElement: 'form',
        containerAttributes: {
          action: '',
          method: 'post',
          novalidate: '',
          class: 'fld-element-settings',
        },
      });
      slideout.on('open', () => {
        // Hold off a sec until it's positioned...
        Garnish.requestAnimationFrame(() => {
          // Focus on the first text input
          slideout.$container.find('.text:first').trigger('focus');
        });
      });

      $cancelBtn.on('click', () => {
        slideout.close();
      });

      if (js) {
        eval(js);
      }

      Craft.initUiElements(slideout.$container);

      return slideout;
    },
  }
);

Craft.FieldLayoutDesigner.Tab = Garnish.Base.extend({
  designer: null,
  uid: null,
  $container: null,
  slideout: null,

  init: function (designer, $container) {
    this.designer = designer;
    this.$container = $container;
    this.$container.data('fld-tab', this);
    this.uid = this.$container.data('uid');

    // New tab?
    if (!this.uid) {
      this.uid = Craft.uuid();
      this.config = {
        uid: this.uid,
        name: this.$container.find('.tabs .tab span').text(),
        elements: [],
      };
      this.$container.data(
        'settings-namespace',
        this.designer.$container
          .data('new-tab-settings-namespace')
          .replace(/\bTAB_UID\b/g, this.uid)
      );
      this.$container.data(
        'settings-html',
        this.designer.$container
          .data('new-tab-settings-html')
          .replace(/\bTAB_UID\b/g, this.uid)
          .replace(/\bTAB_NAME\b/g, this.config.name)
      );
      this.$container.data(
        'settings-js',
        this.designer.$container
          .data('new-tab-settings-js')
          .replace(/\bTAB_UID\b/g, this.uid)
      );
    }

    if (this.designer.settings.customizableTabs) {
      this.settingsNamespace = this.$container.data('settings-namespace');
      this.createMenu();
    }

    // initialize the elements
    const $elements = this.$container.children('.fld-tabcontent').children();

    for (let i = 0; i < $elements.length; i++) {
      this.initElement($($elements[i]));
    }
  },

  createMenu: function () {
    const $editBtn = this.$container.find('.tabs .settings');

    $('<div class="menu" data-align="center"/>')
      .insertAfter($editBtn)
      .append(
        $('<ul/>')
          .append(
            $('<li/>').append(
              $('<a/>', {
                'data-action': 'settings',
                text: Craft.t('app', 'Settings'),
              })
            )
          )
          .append(
            $('<li/>').append(
              $('<a/>', {
                'data-action': 'remove',
                text: Craft.t('app', 'Remove'),
              })
            )
          )
      )
      .append($('<hr/>'))
      .append(
        $('<ul/>')
          .append(
            $('<li/>').append(
              $('<a/>', {
                'data-action': 'moveLeft',
                text: Craft.t('app', 'Move to the left'),
              })
            )
          )
          .append(
            $('<li/>').append(
              $('<a/>', {
                'data-action': 'moveRight',
                text: Craft.t('app', 'Move to the right'),
              })
            )
          )
      );

    let menuBtn = new Garnish.MenuBtn($editBtn, {
      onOptionSelect: this.onTabOptionSelect.bind(this),
    });

    menuBtn.menu.on('show', () => {
      if (this.$container.prev('.fld-tab').length) {
        menuBtn.menu.$container
          .find('[data-action=moveLeft]')
          .removeClass('disabled');
      } else {
        menuBtn.menu.$container
          .find('[data-action=moveLeft]')
          .addClass('disabled');
      }

      if (this.$container.next('.fld-tab').length) {
        menuBtn.menu.$container
          .find('[data-action=moveRight]')
          .removeClass('disabled');
      } else {
        menuBtn.menu.$container
          .find('[data-action=moveRight]')
          .addClass('disabled');
      }
    });
  },

  onTabOptionSelect: function (option) {
    if (!this.designer.settings.customizableTabs) {
      return;
    }

    let $option = $(option);
    let action = $option.data('action');

    switch (action) {
      case 'settings':
        if (!this.slideout) {
          this.createSettings();
        } else {
          this.slideout.open();
        }
        break;
      case 'remove':
        this.destroy();
        break;
      case 'moveLeft':
        let $prev = this.$container.prev('.fld-tab');
        if ($prev.length) {
          this.$container.insertBefore($prev);
          this.updatePositionInConfig();
        }
        break;
      case 'moveRight':
        let $next = this.$container.next('.fld-tab');
        if ($next.length) {
          this.$container.insertAfter($next);
          this.updatePositionInConfig();
        }
        break;
    }
  },

  createSettings: function () {
    const settingsHtml = this.$container.data('settings-html');
    const settingsJs = this.$container.data('settings-js');
    this.slideout = Craft.FieldLayoutDesigner.createSlideout(
      settingsHtml,
      settingsJs
    );

    this.slideout.$container.on('submit', (ev) => {
      ev.preventDefault();
      this.applySettings();
    });
  },

  applySettings: function () {
    if (!this.slideout.$container.find('[name$="[name]"]').val()) {
      alert(Craft.t('app', 'You must specify a tab name.'));
      return;
    }

    // update the UI
    let $submitBtn = this.slideout.$container
      .find('button[type=submit]')
      .addClass('loading');

    const config = $.extend({}, this.config);
    delete config.elements;

    Craft.sendActionRequest('POST', 'fields/apply-layout-tab-settings', {
      data: {
        config: config,
        settingsNamespace: this.settingsNamespace,
        settings: this.slideout.$container.serialize(),
      },
    })
      .then((response) => {
        this.updateConfig((config) =>
          $.extend(response.data.config, {elements: config.elements})
        );
        this.slideout.close();
      })
      .catch((e) => {
        Craft.cp.displayError();
        console.error(e);
      })
      .finally(() => {
        $submitBtn.removeClass('loading');
        this.slideout.close();
      });
  },

  initElement: function ($element) {
    return new Craft.FieldLayoutDesigner.Element(this, $element);
  },

  get index() {
    return this.designer.config.tabs.findIndex((c) => c.uid === this.uid);
  },

  get config() {
    if (!this.uid) {
      throw 'Tab is missing its UID';
    }
    let config = this.designer.config.tabs.find((c) => c.uid === this.uid);
    if (!config) {
      config = {
        uid: this.uid,
        elements: [],
      };
      this.config = config;
    }
    return config;
  },

  set config(config) {
    // Is the name changing?
    if (config.name && config.name !== this.config.name) {
      this.$container.find('.tabs .tab span').text(config.name);
    }

    const designerConfig = this.designer.config;
    const index = this.index;
    if (index !== -1) {
      designerConfig.tabs[index] = config;
    } else {
      const newIndex = $.inArray(
        this.$container[0],
        this.$container.parent().children('.fld-tab')
      );
      designerConfig.tabs.splice(newIndex, 0, config);
    }
    this.designer.config = designerConfig;
  },

  updateConfig: function (callback) {
    const config = callback(this.config);
    if (config !== false) {
      this.config = config;
    }
  },

  updatePositionInConfig: function () {
    this.designer.updateConfig((config) => {
      const tabConfig = this.config;
      const oldIndex = this.index;
      const newIndex = $.inArray(
        this.$container[0],
        this.$container.parent().children('.fld-tab')
      );
      if (oldIndex !== -1) {
        config.tabs.splice(oldIndex, 1);
      }
      config.tabs.splice(newIndex, 0, tabConfig);
      return config;
    });
  },

  destroy: function () {
    this.designer.updateConfig((config) => {
      const index = this.index;
      if (index === -1) {
        return false;
      }
      config.tabs.splice(index, 1);
      return config;
    });

    // First destroy the tab's elements
    let $elements = this.$container.find('.fld-element');
    for (let i = 0; i < $elements.length; i++) {
      $elements.eq(i).data('fld-element').destroy();
    }

    this.designer.tabGrid.removeItems(this.$container);
    this.designer.tabDrag.removeItems(this.$container);
    this.$container.remove();

    this.base();
  },
});

Craft.FieldLayoutDesigner.Element = Garnish.Base.extend({
  tab: null,
  $container: null,
  $settingsContainer: null,
  $editBtn: null,

  uid: null,
  isField: false,
  attribute: null,
  requirable: false,
  hasCustomWidth: false,
  hasSettings: false,
  settingsNamespace: null,
  slideout: null,

  init: function (tab, $container) {
    this.tab = tab;
    this.$container = $container;
    this.$container.data('fld-element', this);
    this.uid = this.$container.data('uid');

    // New element?
    if (!this.uid) {
      this.uid = Craft.uuid();
      this.config = $.extend(this.$container.data('config'), {uid: this.uid});
    }

    this.isField = this.$container.hasClass('fld-field');
    this.requirable =
      this.isField && Garnish.hasAttr(this.$container, 'data-requirable');

    if (this.isField) {
      this.attribute = this.$container.data('attribute');
    }

    this.settingsNamespace = this.$container
      .data('settings-namespace')
      .replace(/\bELEMENT_UID\b/g, this.uid);
    let settingsHtml = (this.$container.data('settings-html') || '').replace(
      /\bELEMENT_UID\b/g,
      this.uid
    );
    let isRequired =
      this.requirable && this.$container.hasClass('fld-required');
    this.hasCustomWidth =
      this.tab.designer.settings.customizableUi &&
      Garnish.hasAttr(this.$container, 'data-has-custom-width');
    this.hasSettings = settingsHtml || this.requirable;

    if (this.hasSettings) {
      // create the setting container
      this.$settingsContainer = $('<div/>', {
        class: 'hidden',
      });

      // create the edit button
      this.$editBtn = $('<a/>', {
        role: 'button',
        tabindex: 0,
        class: 'settings icon',
        title: Craft.t('app', 'Edit'),
      });

      this.$editBtn.on('click', () => {
        if (!this.slideout) {
          this.createSettings(settingsHtml, isRequired);
        } else {
          this.slideout.open();
        }
      });
    }

    this.initUi();

    // cleanup
    this.$container.attr('data-keywords', null);
    this.$container.attr('data-settings-html', null);
  },

  initUi: function () {
    if (this.hasCustomWidth) {
      let widthSlider = new Craft.SlidePicker(this.config.width || 100, {
        min: 25,
        max: 100,
        step: 25,
        valueLabel: (width) => {
          return Craft.t('app', '{pct} width', {pct: `${width}%`});
        },
        onChange: (width) => {
          this.updateConfig((config) => {
            config.width = width;
            return config;
          });
        },
      });
      widthSlider.$container.appendTo(this.$container);
    }

    if (this.hasSettings) {
      this.$editBtn.appendTo(this.$container);
    }
  },

  createSettings: function (settingsHtml, isRequired) {
    const settingsJs = (this.$container.data('settings-js') || '').replace(
      /\bELEMENT_UID\b/g,
      this.uid
    );
    this.slideout = Craft.FieldLayoutDesigner.createSlideout(
      settingsHtml,
      settingsJs
    );

    this.slideout.$container.on('submit', (ev) => {
      ev.preventDefault();
      this.applySettings();
    });

    if (this.requirable) {
      const $fieldsContainer = this.slideout.$container.find('.fields:first');
      Craft.ui
        .createLightswitchField({
          label: Craft.t('app', 'Required'),
          name: `${this.settingsNamespace}[required]`,
          on: isRequired,
        })
        .prependTo($fieldsContainer);
    }

    this.trigger('createSettings');
  },

  applySettings: function () {
    // update the UI
    let $submitBtn = this.slideout.$container
      .find('button[type=submit]')
      .addClass('loading');

    Craft.sendActionRequest('POST', 'fields/apply-layout-element-settings', {
      data: {
        config: this.config,
        settingsNamespace: this.settingsNamespace,
        settings: this.slideout.$container.serialize(),
      },
    })
      .then((response) => {
        this.config = response.data.config;
        this.$editBtn.detach();
        this.$container.html($(response.data.selectorHtml).html());
        if (response.data.hasConditions) {
          this.$container.addClass('has-conditions');
        } else {
          this.$container.removeClass('has-conditions');
        }
        this.initUi();
      })
      .catch((e) => {
        Craft.cp.displayError();
        console.error(e);
      })
      .finally(() => {
        $submitBtn.removeClass('loading');
        this.updateRequiredClass();
        this.slideout.close();
      });
  },

  updateRequiredClass: function () {
    if (!this.requirable) {
      return;
    }

    if (this.config.required) {
      this.$container.addClass('fld-required');
    } else {
      this.$container.removeClass('fld-required');
    }
  },

  get index() {
    const tabConfig = this.tab.config;
    if (typeof tabConfig === 'undefined') {
      return -1;
    }
    return tabConfig.elements.findIndex((c) => c.uid === this.uid);
  },

  get config() {
    if (!this.uid) {
      throw 'Tab is missing its UID';
    }
    let config = this.tab.config.elements.find((c) => c.uid === this.uid);
    if (!config) {
      config = {
        uid: this.uid,
      };
      this.config = config;
    }
    return config;
  },

  set config(config) {
    const tabConfig = this.tab.config;
    const index = this.index;
    if (index !== -1) {
      tabConfig.elements[index] = config;
    } else {
      const newIndex = $.inArray(
        this.$container[0],
        this.$container.parent().children('.fld-element')
      );
      tabConfig.elements.splice(newIndex, 0, config);
    }
    this.tab.config = tabConfig;
  },

  updateConfig: function (callback) {
    const config = callback(this.config);
    if (config !== false) {
      this.config = config;
    }
  },

  updatePositionInConfig: function () {
    this.tab.updateConfig((config) => {
      const elementConfig = this.config;
      const oldIndex = this.index;
      const newIndex = $.inArray(
        this.$container[0],
        this.$container.parent().children('.fld-element')
      );
      if (oldIndex !== -1) {
        config.elements.splice(oldIndex, 1);
      }
      config.elements.splice(newIndex, 0, elementConfig);
      return config;
    });
  },

  destroy: function () {
    this.tab.updateConfig((config) => {
      const index = this.index;
      if (index === -1) {
        return false;
      }
      config.elements.splice(index, 1);
      return config;
    });

    this.tab.designer.elementDrag.removeItems(this.$container);
    this.$container.remove();

    if (this.isField) {
      this.tab.designer.removeFieldByHandle(this.attribute);
    }

    this.base();
  },
});

Craft.FieldLayoutDesigner.BaseDrag = Garnish.Drag.extend({
  designer: null,
  $insertion: null,
  showingInsertion: false,
  $caboose: null,

  /**
   * Constructor
   */
  init: function (designer, settings) {
    this.designer = designer;
    this.base(this.findItems(), settings);
  },

  /**
   * On Drag Start
   */
  onDragStart: function () {
    this.base();

    // Create the insertion
    this.$insertion = this.createInsertion();

    // Add the caboose
    this.$caboose = this.createCaboose();
    this.$items = $().add(this.$items.add(this.$caboose));

    Garnish.$bod.addClass('dragging');
  },

  removeCaboose: function () {
    this.$items = this.$items.not(this.$caboose);
    this.$caboose.remove();
  },

  swapDraggeeWithInsertion: function () {
    this.$insertion.insertBefore(this.$draggee);
    this.$draggee.detach();
    this.$items = $().add(this.$items.not(this.$draggee).add(this.$insertion));
    this.showingInsertion = true;
  },

  swapInsertionWithDraggee: function () {
    this.$insertion.replaceWith(this.$draggee);
    this.$items = $().add(this.$items.not(this.$insertion).add(this.$draggee));
    this.showingInsertion = false;
  },

  /**
   * Sets the item midpoints up front so we don't have to keep checking on every mouse move
   */
  setMidpoints: function () {
    for (let i = 0; i < this.$items.length; i++) {
      let $item = $(this.$items[i]);
      let offset = $item.offset();

      // Skip library elements
      if ($item.hasClass('unused')) {
        continue;
      }

      $item.data('midpoint', {
        left: offset.left + $item.outerWidth() / 2,
        top: offset.top + $item.outerHeight() / 2,
      });
    }
  },

  /**
   * Returns the closest item to the cursor.
   */
  getClosestItem: function () {
    this.getClosestItem._closestItem = null;
    this.getClosestItem._closestItemMouseDiff = null;

    for (
      this.getClosestItem._i = 0;
      this.getClosestItem._i < this.$items.length;
      this.getClosestItem._i++
    ) {
      this.getClosestItem._$item = $(this.$items[this.getClosestItem._i]);

      this.getClosestItem._midpoint =
        this.getClosestItem._$item.data('midpoint');
      if (!this.getClosestItem._midpoint) {
        continue;
      }

      this.getClosestItem._mouseDiff = Garnish.getDist(
        this.getClosestItem._midpoint.left,
        this.getClosestItem._midpoint.top,
        this.mouseX,
        this.mouseY
      );

      if (
        this.getClosestItem._closestItem === null ||
        this.getClosestItem._mouseDiff <
          this.getClosestItem._closestItemMouseDiff
      ) {
        this.getClosestItem._closestItem = this.getClosestItem._$item[0];
        this.getClosestItem._closestItemMouseDiff =
          this.getClosestItem._mouseDiff;
      }
    }

    return this.getClosestItem._closestItem;
  },

  checkForNewClosestItem: function () {
    // Is there a new closest item?
    this.checkForNewClosestItem._closestItem = this.getClosestItem();

    if (this.checkForNewClosestItem._closestItem === this.$insertion[0]) {
      return;
    }

    if (
      this.showingInsertion &&
      $.inArray(this.$insertion[0], this.$items) <
        $.inArray(this.checkForNewClosestItem._closestItem, this.$items) &&
      $.inArray(this.checkForNewClosestItem._closestItem, this.$caboose) === -1
    ) {
      this.$insertion.insertAfter(this.checkForNewClosestItem._closestItem);
    } else {
      this.$insertion.insertBefore(this.checkForNewClosestItem._closestItem);
    }

    this.$items = $().add(this.$items.add(this.$insertion));
    this.showingInsertion = true;
    this.designer.tabGrid.refreshCols(true);
    this.setMidpoints();
  },

  /**
   * On Drag Stop
   */
  onDragStop: function () {
    if (this.showingInsertion) {
      this.swapInsertionWithDraggee();
    }

    this.removeCaboose();

    this.designer.tabGrid.refreshCols(true);

    // return the helpers to the draggees
    let offset = this.$draggee.offset();
    if (!offset || (offset.top === 0 && offset.left === 0)) {
      this.$draggee
        .css({
          display: this.draggeeDisplay,
          visibility: 'visible',
          opacity: 0,
        })
        .velocity({opacity: 1}, Garnish.FX_DURATION);
      this.helpers[0].velocity({opacity: 0}, Garnish.FX_DURATION, () => {
        this._showDraggee();
      });
    } else {
      this.returnHelpersToDraggees();
    }

    this.base();

    Garnish.$bod.removeClass('dragging');
  },
});

Craft.FieldLayoutDesigner.TabDrag = Craft.FieldLayoutDesigner.BaseDrag.extend({
  /**
   * Constructor
   */
  init: function (designer) {
    let settings = {
      handle: '.tab',
    };

    this.base(designer, settings);
  },

  findItems: function () {
    return this.designer.$tabContainer.find('> div.fld-tab');
  },

  /**
   * On Drag Start
   */
  onDragStart: function () {
    this.base();
    this.swapDraggeeWithInsertion();
    this.setMidpoints();
  },

  swapDraggeeWithInsertion: function () {
    this.base();
    this.designer.tabGrid.removeItems(this.$draggee);
    this.designer.tabGrid.addItems(this.$insertion);
  },

  swapInsertionWithDraggee: function () {
    this.base();
    this.designer.tabGrid.removeItems(this.$insertion);
    this.designer.tabGrid.addItems(this.$draggee);
  },

  /**
   * On Drag
   */
  onDrag: function () {
    this.checkForNewClosestItem();
    this.base();
  },

  /**
   * On Drag Stop
   */
  onDragStop: function () {
    this.base();

    // "show" the tab, but make it invisible
    this.$draggee.css({
      display: this.draggeeDisplay,
      visibility: 'hidden',
    });

    this.$draggee.data('fld-tab').updatePositionInConfig();
  },

  /**
   * Creates the caboose
   */
  createCaboose: function () {
    let $caboose = $('<div class="fld-tab fld-tab-caboose"/>').appendTo(
      this.designer.$tabContainer
    );
    this.designer.tabGrid.addItems($caboose);
    return $caboose;
  },

  /**
   * Removes the caboose
   */
  removeCaboose: function () {
    this.base();
    this.designer.tabGrid.removeItems(this.$caboose);
  },

  /**
   * Creates the insertion
   */
  createInsertion: function () {
    let $tab = this.$draggee.find('.tab');

    return $(`
<div class="fld-tab fld-insertion" style="height: ${this.$draggee.height()}px;">
  <div class="tabs"><div class="tab sel draggable" style="width: ${$tab.width()}px; height: ${$tab.height()}px;"></div></div>
  <div class="fld-tabcontent" style="height: ${this.$draggee
    .find('.fld-tabcontent')
    .height()}px;"></div>
</div>
`);
  },
});

Craft.FieldLayoutDesigner.ElementDrag =
  Craft.FieldLayoutDesigner.BaseDrag.extend({
    draggingLibraryElement: false,
    draggingField: false,
    originalTab: null,

    /**
     * On Drag Start
     */
    onDragStart: function () {
      this.base();

      // Are we dragging an element from the library?
      this.draggingLibraryElement = this.$draggee.hasClass('unused');

      // Is it a field?
      this.draggingField = this.$draggee.hasClass('fld-field');

      // keep UI elements visible
      if (this.draggingLibraryElement && !this.draggingField) {
        this.$draggee.css({
          display: this.draggeeDisplay,
          visibility: 'visible',
        });
      }

      // Swap the draggee with the insertion if dragging a selected item
      if (!this.draggingLibraryElement) {
        this.originalTab = this.$draggee.closest('.fld-tab').data('fld-tab');
        this.swapDraggeeWithInsertion();
      } else {
        this.originalTab = null;
      }

      this.setMidpoints();
    },

    /**
     * On Drag
     */
    onDrag: function () {
      if (this.isDraggeeMandatory() || this.isHoveringOverTab()) {
        this.checkForNewClosestItem();
      } else if (this.showingInsertion) {
        this.$insertion.remove();
        this.$items = $().add(this.$items.not(this.$insertion));
        this.showingInsertion = false;
        this.designer.tabGrid.refreshCols(true);
        this.setMidpoints();
      }

      this.base();
    },

    isDraggeeMandatory: function () {
      return Garnish.hasAttr(this.$draggee, 'data-mandatory');
    },

    isHoveringOverTab: function () {
      for (let i = 0; i < this.designer.tabGrid.$items.length; i++) {
        if (
          Garnish.hitTest(
            this.mouseX,
            this.mouseY,
            this.designer.tabGrid.$items.eq(i)
          )
        ) {
          return true;
        }
      }

      return false;
    },

    findItems: function () {
      // Return all of the used + unused fields
      return this.designer.$tabContainer
        .find('.fld-element')
        .add(this.designer.$sidebar.find('.fld-element'));
    },

    /**
     * Creates the caboose
     */
    createCaboose: function () {
      let $caboose = $();
      let $fieldContainers = this.designer.$tabContainer.find(
        '> .fld-tab > .fld-tabcontent'
      );

      for (let i = 0; i < $fieldContainers.length; i++) {
        $caboose = $caboose.add($('<div/>').appendTo($fieldContainers[i]));
      }

      return $caboose;
    },

    /**
     * Creates the insertion
     */
    createInsertion: function () {
      return $(
        `<div class="fld-element fld-insertion" style="height: ${this.$draggee.outerHeight()}px;"/>`
      );
    },

    /**
     * On Drag Stop
     */
    onDragStop: function () {
      let showingInsertion = this.showingInsertion;
      if (showingInsertion) {
        if (this.draggingLibraryElement) {
          // Create a new element based on that one
          const $element = this.$draggee.clone().removeClass('unused');

          if (this.draggingField) {
            // Hide the library field
            this.$draggee
              .css({visibility: 'inherit', display: 'field'})
              .addClass('hidden');

            // Hide the group too?
            if (
              this.$draggee.siblings('.fld-field:not(.hidden)').length === 0
            ) {
              this.$draggee.closest('.fld-field-group').addClass('hidden');
            }
          }

          // Set this.$draggee to the clone, as if we were dragging that all along
          this.$draggee = $element;

          // Remember it for later
          this.addItems($element);
        }
      } else if (!this.draggingLibraryElement) {
        let $libraryElement = this.draggingField
          ? this.designer.$fields.filter(
              `[data-attribute="${this.$draggee.data('attribute')}"]:first`
            )
          : this.designer.$uiLibraryElements.filter(
              `[data-type="${this.$draggee.data('type')}"]:first`
            );

        if (this.draggingField) {
          // show the field in the library
          $libraryElement.removeClass('hidden');
          $libraryElement.closest('.fld-field-group').removeClass('hidden');
        }

        // Destroy the original element
        this.$draggee.data('fld-element').destroy();

        // Set this.$draggee to the library element, as if we were dragging that all along
        this.$draggee = $libraryElement;
      }

      this.base();

      this.$draggee.css({
        display: this.draggeeDisplay,
        visibility:
          this.draggingField || showingInsertion ? 'hidden' : 'visible',
      });

      if (showingInsertion) {
        const tab = this.$draggee.closest('.fld-tab').data('fld-tab');
        let element;

        if (this.draggingLibraryElement) {
          element = tab.initElement(this.$draggee);
        } else {
          element = this.$draggee.data('fld-element');

          // New tab?
          if (tab !== this.originalTab) {
            const config = element.config;

            this.originalTab.updateConfig((config) => {
              const index = element.index;
              if (index === -1) {
                return false;
              }
              config.elements.splice(index, 1);
              return config;
            });

            this.$draggee.data('fld-element').tab = tab;
            element.config = config;
          }
        }

        element.updatePositionInConfig();
      }
    },
  });
