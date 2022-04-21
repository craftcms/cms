/** global: Craft */
/** global: Garnish */
/**
 * Element editor
 */
Craft.BaseElementEditor = Garnish.Base.extend(
  {
    $element: null,
    elementId: null,
    siteId: null,
    deltaNames: null,
    initialData: null,

    $header: null,
    $toolbar: null,
    $tabContainer: null,
    $editLink: null,
    $sidebarBtn: null,
    $loadSpinner: null,

    $body: null,
    $fieldsContainer: null,

    $sidebar: null,

    $footer: null,
    $siteSelectContainer: null,
    $siteSelect: null,
    $siteSpinner: null,
    $cancelBtn: null,
    $saveBtn: null,
    $saveSpinner: null,

    slideout: null,
    tabManager: null,
    showingSidebar: false,

    cancelToken: null,
    ignoreFailedRequest: false,
    initialDeltaValues: null,

    init: function (element, settings) {
      // Param mapping
      if (typeof settings === 'undefined' && $.isPlainObject(element)) {
        // (settings)
        settings = element;
        element = null;
      }

      this.$element = $(element);
      this.setSettings(settings, Craft.BaseElementEditor.defaults);

      // Header
      this.$header = $('<header/>', {class: 'pane-header'});
      this.$toolbar = $('<div/>', {class: 'ee-toolbar'}).appendTo(this.$header);
      this.$tabContainer = $('<div/>', {class: 'pane-tabs'}).appendTo(
        this.$toolbar
      );
      this.$loadSpinner = $('<div/>', {
        class: 'spinner',
        title: Craft.t('app', 'Loading'),
        'aria-label': Craft.t('app', 'Loading'),
      }).appendTo(this.$toolbar);
      this.$editLink = $('<a/>', {
        target: '_blank',
        class: 'btn hidden',
        title: Craft.t('app', 'Open the full edit page in a new tab'),
        'aria-label': Craft.t('app', 'Open the full edit page in a new tab'),
        'data-icon': 'external',
      }).appendTo(this.$toolbar);
      this.$sidebarBtn = $('<button/>', {
        type: 'button',
        class: 'btn hidden sidebar-btn',
        title: Craft.t('app', 'Show sidebar'),
        'aria-label': Craft.t('app', 'Show sidebar'),
        'data-icon': `sidebar-${Garnish.ltr ? 'right' : 'left'}`,
      }).appendTo(this.$toolbar);

      this.addListener(this.$sidebarBtn, 'click', (ev) => {
        ev.preventDefault();
        if (!this.showingSidebar) {
          this.showSidebar();
        } else {
          this.hideSidebar();
        }
      });

      // Body
      this.$body = $('<div/>', {class: 'ee-body'});

      // Fields
      this.$fieldsContainer = $('<div/>', {class: 'fields'}).appendTo(
        this.$body
      );

      // Sidebar
      this.$sidebar = $('<div/>', {class: 'ee-sidebar hidden'}).appendTo(
        this.$body
      );
      Craft.trapFocusWithin(this.$sidebar);

      // Footer
      this.$footer = $('<div/>', {class: 'ee-footer hidden'});
      const $siteSelectOuterContainer = $('<div/>', {
        class: 'ee-site-select',
      }).appendTo(this.$footer);
      this.$siteSelectContainer = $('<div/>', {
        class: 'select hidden',
      }).appendTo($siteSelectOuterContainer);
      this.$siteSelect = $('<select/>').appendTo(this.$siteSelectContainer);
      this.$siteSpinner = $('<div/>', {class: 'spinner hidden'}).appendTo(
        $siteSelectOuterContainer
      );
      this.$cancelBtn = $('<button/>', {
        type: 'button',
        class: 'btn',
        text: Craft.t('app', 'Cancel'),
      }).appendTo(this.$footer);
      this.$saveBtn = $('<button/>', {
        type: 'submit',
        class: 'btn submit',
        text: Craft.t('app', 'Save'),
      }).appendTo(this.$footer);
      this.$saveSpinner = $('<div/>', {class: 'spinner hidden'}).appendTo(
        this.$footer
      );

      let $contents = this.$header.add(this.$body).add(this.$footer);

      // Create the slideout
      this.slideout = new Craft.Slideout($contents, {
        containerElement: 'form',
        containerAttributes: {
          action: '',
          method: 'post',
          novalidate: '',
          class: 'element-editor',
        },
        closeOnEsc: false,
        closeOnShadeClick: false,
      });
      this.slideout.$container.data('elementEditor', this);
      this.slideout.on('beforeClose', () => {
        this.hideSidebar();
      });
      this.slideout.on('close', () => {
        this.trigger('closeSlideout');
        this.destroy();
      });

      // Register shortcuts & events
      Garnish.uiLayerManager.registerShortcut(
        {
          keyCode: Garnish.S_KEY,
          ctrl: true,
        },
        () => {
          this.saveElement();
        }
      );
      Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, () => {
        this.maybeCloseSlideout();
      });
      this.addListener(this.$cancelBtn, 'click', () => {
        this.maybeCloseSlideout();
      });
      this.addListener(this.slideout.$shade, 'click', () => {
        this.maybeCloseSlideout();
      });
      this.addListener(this.slideout.$container, 'click', (ev) => {
        const $target = $(event.target);

        if (
          this.showingSidebar &&
          !$target.closest(this.$sidebarBtn).length &&
          !$target.closest(this.$sidebar).length
        ) {
          this.hideSidebar();
        }
      });
      this.addListener(this.slideout.$container, 'submit', (ev) => {
        ev.preventDefault();
        this.saveElement();
      });
      this.addListener(this.$siteSelect, 'change', 'switchSite');

      this.load().then(() => {
        this.onShowHud();
        this.onCreateForm(this.$body);
      });
    },

    setElementAttribute: function (name, value) {
      if (!this.settings.attributes) {
        this.settings.attributes = {};
      }

      if (value === null) {
        delete this.settings.attributes[name];
      } else {
        this.settings.attributes[name] = value;
      }
    },

    getBaseData: function () {
      const data = $.extend({}, this.settings.params);

      if (this.settings.siteId) {
        data.siteId = this.settings.siteId;
      } else if (this.$element && this.$element.data('site-id')) {
        data.siteId = this.$element.data('site-id');
      }

      if (this.settings.elementId) {
        data.elementId = this.settings.elementId;
      } else if (this.$element && this.$element.data('id')) {
        data.elementId = this.$element.data('id');
      }

      if (this.settings.elementType) {
        data.elementType = this.settings.elementType;
      }

      if (this.settings.attributes) {
        data.attributes = this.settings.attributes;
      }

      if (this.settings.prevalidate) {
        data.prevalidate = 1;
      }

      return data;
    },

    /**
     * @param {object} [data={}]
     * @param {boolean} [refreshInitialData=true]
     * @returns {Promise}
     */
    load: function (data, refreshInitialData) {
      return new Promise((resolve, reject) => {
        this.trigger('beforeLoad');
        // todo: remove this in Craft 4
        this.trigger('beginLoading');
        this.showLoadSpinner();
        this.onBeginLoading();

        if (this.cancelToken) {
          this.ignoreFailedRequest = true;
          this.cancelToken.cancel();
        }

        this.cancelToken = axios.CancelToken.source();

        Craft.sendActionRequest('POST', 'elements/get-editor-html', {
          cancelToken: this.cancelToken.token,
          data: $.extend(this.getBaseData(), data || {}, {
            includeSites: Craft.isMultiSite && this.settings.showSiteSwitcher,
          }),
        })
          .then((response) => {
            this.hideLoadSpinner();
            this.trigger('load');
            // todo: remove this in Craft 4
            this.trigger('endLoading');
            this.onEndLoading();
            this.cancelToken = null;
            if (this.initialDeltaValues === null) {
              this.initialDeltaValues = response.data.initialDeltaValues;
            }
            this.updateForm(response.data, refreshInitialData);
            this.cancelToken = null;
            resolve();
          })
          .catch((e) => {
            this.hideLoadSpinner();
            this.onEndLoading();
            this.cancelToken = null;
            if (!this.ignoreFailedRequest) {
              Craft.cp.displayError();
              reject(e);
            }
            this.ignoreFailedRequest = false;
          });
      });
    },

    showHeader: function () {
      this.$header.removeClass('hidden');
    },

    hideHeader: function () {
      this.$header.addClass('hidden');
    },

    showLoadSpinner: function () {
      this.showHeader();
      this.$loadSpinner.removeClass('hidden');
    },

    hideLoadSpinner: function () {
      this.$loadSpinner.addClass('hidden');
    },

    switchSite: function () {
      if (
        this.isDirty() &&
        !confirm(
          Craft.t(
            'app',
            'Switching sites will lose unsaved changes. Are you sure you want to switch sites?'
          )
        )
      ) {
        this.$siteSelect.val(this.siteId);
        return;
      }

      const newSiteId = this.$siteSelect.val();

      if (newSiteId == this.siteId) {
        return;
      }

      this.$siteSpinner.removeClass('hidden');

      this.load({siteId: newSiteId})
        .then(() => {
          this.$siteSpinner.addClass('hidden');
        })
        .catch(() => {
          this.$siteSpinner.addClass('hidden');
          // Reset the site select
          this.$siteSelect.val(this.siteId);
        });
    },

    /**
     * @param {object} data
     * @param {boolean} [refreshInitialData=true]
     */
    updateForm: function (data, refreshInitialData) {
      // Cleanup
      if (this.tabManager) {
        this.$tabContainer.html('');
        this.tabManager.destroy();
        this.tabManager = null;
      }
      refreshInitialData = refreshInitialData !== false;

      this.siteId = data.siteId;
      this.$fieldsContainer.html(data.fieldHtml);

      let showHeader = false;

      if (data.sites && data.sites.length > 1) {
        showHeader = true;
        this.$siteSelectContainer.removeClass('hidden');
        this.$siteSelect.html('');

        for (let i = 0; i < data.sites.length; i++) {
          const siteInfo = data.sites[i];
          const $option = $('<option/>', {
            value: siteInfo.id,
            text: siteInfo.name,
          }).appendTo(this.$siteSelect);
          if (siteInfo.id == data.siteId) {
            $option.attr('selected', 'selected');
          }
        }
      } else {
        this.$siteSelectContainer.addClass('hidden');
      }

      if (data.tabHtml) {
        showHeader = true;
        this.$tabContainer.replaceWith((this.$tabContainer = $(data.tabHtml)));
        this.tabManager = new Craft.Tabs(this.$tabContainer);
        this.tabManager.on('deselectTab', (ev) => {
          $(ev.$tab.attr('href')).addClass('hidden');
        });
        this.tabManager.on('selectTab', (ev) => {
          $(ev.$tab.attr('href')).removeClass('hidden');
          Garnish.$win.trigger('resize');
          this.$body.trigger('scroll');
        });
      }

      if (data.editUrl) {
        showHeader = true;
        this.$editLink.removeClass('hidden').attr('href', data.editUrl);
      } else if (this.$editLink) {
        this.$editLink.addClass('hidden');
      }

      if (data.sidebarHtml) {
        showHeader = true;
        this.$sidebarBtn.removeClass('hidden');
        this.$sidebar.html(data.sidebarHtml);
        Craft.initUiElements(this.$sidebar);
        new Craft.ElementThumbLoader().load($(this.$sidebar));

        // Open outbound links in new windows
        this.$sidebar.find('a').each(function () {
          if (
            this.hostname.length &&
            typeof $(this).attr('target') === 'undefined'
          ) {
            $(this).attr('target', '_blank');
          }
        });
      } else if (this.$sidebarBtn) {
        this.$sidebarBtn.addClass('hidden');
        this.$sidebar.addClass('hidden');
      }

      if (showHeader) {
        this.showHeader();
      } else {
        this.hideHeader();
      }

      this.$footer.removeClass('hidden');

      if (refreshInitialData) {
        this.deltaNames = data.deltaNames;
      }

      Garnish.requestAnimationFrame(() => {
        Craft.appendHeadHtml(data.headHtml);
        Craft.appendFootHtml(data.footHtml);
        Craft.initUiElements(this.$fieldsContainer);

        if (refreshInitialData) {
          this.initialData = this.slideout.$container.serialize();
        }

        if (!Garnish.isMobileBrowser()) {
          Craft.setFocusWithin(this.$fieldsContainer);
        }

        this.trigger('updateForm');
      });
    },

    showSidebar: function () {
      if (this.showingSidebar) {
        return;
      }

      this.$body.scrollTop(0).addClass('no-scroll');

      this.$sidebar
        .off('transitionend.element-editor')
        .css(this._closedSidebarStyles())
        .removeClass('hidden');

      // Hack to force CSS animations
      this.$sidebar[0].offsetWidth;

      this.$sidebar.css(this._openedSidebarStyles());

      if (!Garnish.isMobileBrowser()) {
        this.$sidebar.one('transitionend.element-editor', () => {
          Craft.setFocusWithin(this.$sidebar);
        });
      }

      this.$sidebarBtn.addClass('active').attr({
        title: Craft.t('app', 'Hide sidebar'),
        'aria-label': Craft.t('app', 'Hide sidebar'),
      });

      Garnish.$win.trigger('resize');
      this.$sidebar.trigger('scroll');

      Garnish.uiLayerManager.addLayer(this.$sidebar);
      Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, () => {
        this.hideSidebar();
      });

      this.showingSidebar = true;
    },

    hideSidebar: function () {
      if (!this.showingSidebar) {
        return;
      }

      this.$body.removeClass('no-scroll');

      this.$sidebar
        .off('transitionend.element-editor')
        .css(this._closedSidebarStyles())
        .one('transitionend.element-editor', () => {
          this.$sidebar.addClass('hidden');
        });

      this.$sidebarBtn.removeClass('active').attr({
        title: Craft.t('app', 'Show sidebar'),
        'aria-label': Craft.t('app', 'Show sidebar'),
      });

      Garnish.uiLayerManager.removeLayer();

      this.showingSidebar = false;
    },

    _openedSidebarStyles: function () {
      return {
        [Garnish.ltr ? 'right' : 'left']: '0',
      };
    },

    _closedSidebarStyles: function () {
      return {
        [Garnish.ltr ? 'right' : 'left']: '-350px',
      };
    },

    saveElement: function () {
      const validators = this.settings.validators;

      if ($.isArray(validators)) {
        for (let i = 0; i < validators.length; i++) {
          if ($.isFunction(validators[i]) && !validators[i].call()) {
            return false;
          }
        }
      }

      this.$saveSpinner.removeClass('hidden');

      let data =
        $.param(this.getBaseData()) +
        '&' +
        this.slideout.$container.serialize();
      data = Craft.findDeltaData(
        this.initialData,
        data,
        this.deltaNames,
        null,
        this.initialDeltaValues
      );

      Craft.postActionRequest(
        'elements/save-element',
        data,
        (response, textStatus) => {
          this.$saveSpinner.addClass('hidden');

          if (textStatus === 'success') {
            if (response.success) {
              if (
                this.$element &&
                this.siteId == this.$element.data('site-id')
              ) {
                // Update the label
                const $title = this.$element.find('.title');
                const $a = $title.find('a');

                if ($a.length && response.cpEditUrl) {
                  $a.attr('href', response.cpEditUrl);
                  $a.text(response.newTitle);
                } else {
                  $title.text(response.newTitle);
                }
              }

              if (
                this.settings.elementType &&
                Craft.elementTypeNames[this.settings.elementType]
              ) {
                Craft.cp.displayNotice(
                  Craft.t('app', '{type} saved.', {
                    type: Craft.elementTypeNames[this.settings.elementType][0],
                  })
                );
              }

              this.closeSlideout();
              this.trigger('saveElement', {
                response: response,
              });
              this.onSaveElement(response);
              this.settings.onSaveElement(response);

              // There may be a new background job that needs to be run
              Craft.cp.runQueue();
            } else {
              this.updateForm(response, false);
              Garnish.shake(this.slideout.$container);
            }
          }
        }
      );
    },

    isDirty: function () {
      return (
        this.initialData !== null &&
        this.slideout.$container.serialize() !== this.initialData
      );
    },

    maybeCloseSlideout: function () {
      if (!this.slideout.isOpen) {
        return;
      }

      if (
        !this.isDirty() ||
        confirm(
          Craft.t(
            'app',
            'Are you sure you want to close the editor? Any changes will be lost.'
          )
        )
      ) {
        this.closeSlideout();
      }
    },

    closeSlideout: function () {
      this.slideout.close();
      this.onHideHud();

      if (this.cancelToken) {
        this.ignoreFailedRequest = true;
        this.cancelToken.cancel();
      }
    },

    destroy: function () {
      this.slideout.destroy();
      delete this.slideout;
      this.base();
    },

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /** @deprecated in 3.7.0 */
    loadHud: function () {
      this.load();
    },
    /** @deprecated in 3.7.0 */
    maybeCloseHud: function () {
      this.maybeCloseSlideout();
    },
    /** @deprecated in 3.7.0 */
    closeHud: function () {
      this.closeSlideout();
    },
    /** @deprecated */
    reloadForm: function (data, callback) {
      this.load(data)
        .then(() => {
          callback('success');
        })
        .catch(() => {
          callback('error');
        });
    },
    /** @deprecated in 3.7.0 */
    onBeginLoading: function () {
      this.settings.onBeginLoading();
    },
    /** @deprecated in 3.7.0 */
    onEndLoading: function () {
      this.settings.onEndLoading();
    },
    /** @deprecated in 3.7.0 */
    onSaveElement: function (response) {},
    /** @deprecated in 3.7.0 */
    onCreateForm: function ($form) {
      this.settings.onCreateForm($form);
    },
    /** @deprecated in 3.7.0 */
    onShowHud: function () {
      this.trigger('showHud');
      this.settings.onShowHud();
    },
    /** @deprecated in 3.7.0 */
    onHideHud: function () {
      this.trigger('hideHud');
      this.settings.onHideHud();
    },
  },
  {
    defaults: {
      showSiteSwitcher: true,
      elementId: null,
      elementType: null,
      siteId: null,
      attributes: null,
      params: null,
      prevalidate: false,
      elementIndex: null,
      onSaveElement: $.noop,
      validators: [],

      /** @deprecated in 3.7.0 */
      onShowHud: $.noop,
      /** @deprecated in 3.7.0 */
      onHideHud: $.noop,
      /** @deprecated in 3.7.0 */
      onBeginLoading: $.noop,
      /** @deprecated in 3.7.0 */
      onEndLoading: $.noop,
      /** @deprecated in 3.7.0 */
      onCreateForm: $.noop,
    },
  }
);
