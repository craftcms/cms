/** global: Craft */
/** global: Garnish */
/**
 * CP Screen Slideout
 */
Craft.CpScreenSlideout = Craft.Slideout.extend(
  {
    action: null,

    namespace: null,

    showingLoadSpinner: false,
    hasTabs: false,
    hasCpLink: false,
    hasSidebar: false,

    $header: null,
    $toolbar: null,
    $tabContainer: null,
    $loadSpinner: null,
    $editLink: null,
    $sidebarBtn: null,

    $body: null,
    $content: null,

    $sidebar: null,

    $footer: null,
    $noticeContainer: null,
    $cancelBtn: null,
    $saveBtn: null,

    tabManager: null,
    showingSidebar: false,

    cancelToken: null,
    ignoreFailedRequest: false,
    fieldsWithErrors: null,

    init: function (action, settings) {
      this.action = action;
      this.setSettings(settings, Craft.CpScreenSlideout.defaults);

      this.fieldsWithErrors = [];

      // Header
      this.$header = $('<header/>', {class: 'pane-header'});
      this.$toolbar = $('<div/>', {class: 'so-toolbar'}).appendTo(this.$header);
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
        class: 'btn header-btn hidden',
        title: Craft.t('app', 'Open the full edit page in a new tab'),
        'aria-label': Craft.t('app', 'Open the full edit page in a new tab'),
        'data-icon': 'external',
      }).appendTo(this.$toolbar);
      this.$sidebarBtn = $('<button/>', {
        type: 'button',
        class: 'btn header-btn hidden sidebar-btn',
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
      this.$body = $('<div/>', {class: 'so-body'});

      // Content
      this.$content = $('<div/>', {class: 'so-content'}).appendTo(this.$body);

      // Sidebar
      this.$sidebar = $('<div/>', {
        class: 'so-sidebar details hidden',
      }).appendTo(this.$body);
      Craft.trapFocusWithin(this.$sidebar);

      // Footer
      this.$footer = $('<div/>', {class: 'so-footer hidden'});
      this.$noticeContainer = $('<div/>', {class: 'so-notice'}).appendTo(
        this.$footer
      );
      $('<div/>', {class: 'flex-grow'}).appendTo(this.$footer);
      const $btnContainer = $('<div/>', {class: 'flex flex-nowrap'}).appendTo(
        this.$footer
      );
      this.$cancelBtn = $('<button/>', {
        type: 'button',
        class: 'btn',
        text: Craft.t('app', 'Cancel'),
      }).appendTo($btnContainer);
      this.$saveBtn = Craft.ui
        .createSubmitButton({
          label: Craft.t('app', 'Save'),
          spinner: true,
        })
        .appendTo($btnContainer);

      let $contents = this.$header.add(this.$body).add(this.$footer);

      this.base($contents, {
        containerElement: 'form',
        containerAttributes: {
          id: `cp-screen-${Math.floor(Math.random() * 100000000)}`,
          action: '',
          method: 'post',
          novalidate: '',
          class: 'cp-screen',
        },
        closeOnEsc: false,
        closeOnShadeClick: false,
      });

      this.$container.data('cpScreen', this);
      this.on('beforeClose', () => {
        this.hideSidebar();
      });

      // Register shortcuts & events
      Garnish.uiLayerManager.registerShortcut(
        {
          keyCode: Garnish.S_KEY,
          ctrl: true,
        },
        (ev) => {
          this.handleSubmit(ev);
        }
      );
      Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, () => {
        this.closeMeMaybe();
      });
      this.addListener(this.$cancelBtn, 'click', () => {
        this.closeMeMaybe();
      });
      this.addListener(this.$shade, 'click', () => {
        this.closeMeMaybe();
      });
      this.addListener(this.$container, 'click', (ev) => {
        const $target = $(event.target);

        if (
          this.showingSidebar &&
          !$target.closest(this.$sidebarBtn).length &&
          !$target.closest(this.$sidebar).length
        ) {
          this.hideSidebar();
        }
      });
      this.addListener(this.$container, 'submit', 'handleSubmit');

      this.load();
    },

    /**
     * @param {object} [data={}]
     * @param {boolean} [refreshInitialData=true]
     * @returns {Promise}
     */
    load: function (data, refreshInitialData) {
      return new Promise((resolve, reject) => {
        this.trigger('beforeLoad');
        this.showLoadSpinner();

        if (this.cancelToken) {
          this.ignoreFailedRequest = true;
          this.cancelToken.cancel();
        }

        this.cancelToken = axios.CancelToken.source();

        Craft.sendActionRequest(
          'GET',
          this.action,
          $.extend(
            {
              params: Object.assign({}, this.getParams(), this.settings.params),
              cancelToken: this.cancelToken.token,
              headers: {
                'X-Craft-Container-Id': this.$container.attr('id'),
              },
            },
            this.settings.requestOptions
          )
        )
          .then((response) => {
            this.update(response.data)
              .then(() => {
                if (refreshInitialData !== false) {
                  this.$container.data('delta-names', response.data.deltaNames);
                  this.$container.data(
                    'initial-delta-values',
                    response.data.initialDeltaValues
                  );
                  this.$container.data(
                    'initialSerializedValue',
                    this.$container.serialize()
                  );
                }
                resolve();
              })
              .catch((e) => {
                reject(e);
              });
          })
          .catch((e) => {
            if (!this.ignoreFailedRequest) {
              Craft.cp.displayError();
              reject(e);
            }
            this.ignoreFailedRequest = false;
          })
          .finally(() => {
            this.hideLoadSpinner();
            this.cancelToken = null;
          });
      });
    },

    getParams: function () {
      return {};
    },

    updateHeaderVisibility: function () {
      // Should the header be shown regardless of viewport size?
      const forceShow =
        this.settings.showHeader ||
        this.hasTabs ||
        this.hasCpLink ||
        this.showingLoadSpinner;

      if (forceShow || this.hasSidebar) {
        this.$header.removeClass('hidden');
      } else {
        this.$header.addClass('hidden');
      }

      if (forceShow) {
        this.$header.addClass('so-visible');
      } else {
        this.$header.removeClass('so-visible');
      }
    },

    showLoadSpinner: function () {
      this.$loadSpinner.removeClass('hidden');
      this.showingLoadSpinner = true;
      this.updateHeaderVisibility();
    },

    hideLoadSpinner: function () {
      this.$loadSpinner.addClass('hidden');
      this.showingLoadSpinner = false;
      this.updateHeaderVisibility();
    },

    /**
     * @param {object} data
     * @return {Promise}
     */
    update: function (data) {
      return new Promise((resolve) => {
        this.namespace = data.namespace;
        this.$content.html(data.content);

        this.updateTabs(data.tabs);

        if (data.formAttributes) {
          Craft.setElementAttributes(this.$container, data.formAttributes);
        }

        if (data.editUrl) {
          this.$editLink.removeClass('hidden').attr('href', data.editUrl);
          this.hasCpLink = true;
        } else {
          this.$editLink.addClass('hidden');
          this.hasCpLink = false;
        }

        if (data.sidebar) {
          this.$container.addClass('has-sidebar');
          this.$sidebarBtn.removeClass('hidden');
          this.$sidebar.html(data.sidebar);

          // Open outbound links in new windows
          this.$sidebar.find('a').each(function () {
            if (
              this.hostname.length &&
              typeof $(this).attr('target') === 'undefined'
            ) {
              $(this).attr('target', '_blank');
            }
          });

          this.hasSidebar = true;
        } else {
          this.$container.removeClass('has-sidebar');
          this.$sidebarBtn.addClass('hidden');
          this.$sidebar.addClass('hidden').html('');
          this.hasSidebar = false;
        }

        if (data.notice) {
          this.$noticeContainer.html(data.notice);
        } else {
          this.$noticeContainer.empty();
        }

        this.updateHeaderVisibility();
        this.$footer.removeClass('hidden');

        Garnish.requestAnimationFrame(() => {
          Craft.appendHeadHtml(data.headHtml);
          Craft.appendBodyHtml(data.bodyHtml);

          Craft.initUiElements(this.$content);
          new Craft.ElementThumbLoader().load($(this.$content));

          if (data.sidebar) {
            Craft.initUiElements(this.$sidebar);
            new Craft.ElementThumbLoader().load(this.$sidebar);
          }

          if (!Garnish.isMobileBrowser()) {
            Craft.setFocusWithin(this.$content);
          }

          resolve();
          this.trigger('load');
        });
      });
    },

    updateTabs: function (tabs) {
      if (this.tabManager) {
        this.tabManager.destroy();
        this.tabManager = null;
        this.$tabContainer.html('');
      }

      this.hasTabs = !!tabs;

      if (this.hasTabs) {
        const $tabContainer = $(tabs);
        this.$tabContainer.replaceWith($tabContainer);
        this.$tabContainer = $tabContainer;
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
    },

    showSidebar: function () {
      if (this.showingSidebar) {
        return;
      }

      this.$body.scrollTop(0).addClass('no-scroll');

      this.$sidebar
        .off('transitionend.so')
        .css(this._closedSidebarStyles())
        .removeClass('hidden');

      // Hack to force CSS animations
      this.$sidebar[0].offsetWidth;

      this.$sidebar.css(this._openedSidebarStyles());

      if (!Garnish.isMobileBrowser()) {
        this.$sidebar.one('transitionend.so', () => {
          Craft.setFocusWithin(this.$sidebar);
        });
      }

      this.$sidebarBtn.addClass('active').attr({
        title: Craft.t('app', 'Hide sidebar'),
        'aria-label': Craft.t('app', 'Hide sidebar'),
      });

      Garnish.$win.trigger('resize');
      this.$sidebar.trigger('scroll');

      Garnish.uiLayerManager.addLayer();
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
        .off('transitionend.so')
        .css(this._closedSidebarStyles())
        .one('transitionend.so', () => {
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

    showSubmitSpinner: function () {
      this.$saveBtn.addClass('loading');
    },

    hideSubmitSpinner: function () {
      this.$saveBtn.removeClass('loading');
    },

    handleSubmit: function (ev) {
      ev.preventDefault();
      this.submit();
    },

    submit: function () {
      this.showSubmitSpinner();

      const data = Craft.findDeltaData(
        this.$container.data('initialSerializedValue'),
        this.$container.serialize(),
        this.$container.data('delta-names'),
        null,
        this.$container.data('initial-delta-values')
      );

      Craft.sendActionRequest('POST', null, {
        data,
        headers: {
          'X-Craft-Namespace': this.namespace,
        },
      })
        .then((response) => {
          this.handleSubmitResponse(response);
        })
        .catch(() => {
          this.handleSubmitError();
        })
        .finally(() => {
          this.hideSubmitSpinner();
        });
    },

    handleSubmitResponse: function (response) {
      this.clearErrors();
      const data = response.data || {};
      if (data.message) {
        Craft.cp.displayNotice(data.message);
      }
      this.trigger('submit', {
        response: response,
        data: (data.modelName && data[data.modelName]) || {},
      });
      if (this.settings.closeOnSubmit) {
        this.close();
      }
    },

    handleSubmitError: function (error) {
      if (
        !error.isAxiosError ||
        !error.response ||
        !error.response.status === 400
      ) {
        Craft.cp.displayError();
        throw error;
      }

      const data = error.response.data || {};
      Craft.cp.displayError(data.message);
      if (data.errors) {
        this.showErrors(data.errors);
      }
    },

    /**
     * @param {string[]} errors
     */
    showErrors: function (errors) {
      this.clearErrors();

      Object.entries(errors).forEach(([name, fieldErrors]) => {
        const $field = this.$container.find(`[data-attribute="${name}"]`);
        if ($field) {
          Craft.ui.addErrorsToField($field, fieldErrors);
          this.fieldsWithErrors.push($field);
        }
      });
    },

    clearErrors: function () {
      this.fieldsWithErrors.forEach(($field) => {
        Craft.ui.clearErrorsFromField($field);
      });
    },

    isDirty: function () {
      return (
        typeof this.$container.data('initialSerializedValue') !== 'undefined' &&
        this.$container.serialize() !==
          this.$container.data('initialSerializedValue')
      );
    },

    closeMeMaybe: function () {
      if (!this.isOpen) {
        return;
      }

      if (
        !this.isDirty() ||
        confirm(
          Craft.t(
            'app',
            'Are you sure you want to close this screen? Any changes will be lost.'
          )
        )
      ) {
        this.close();
      }
    },

    close: function () {
      this.base();

      if (this.cancelToken) {
        this.ignoreFailedRequest = true;
        this.cancelToken.cancel();
      }
    },
  },
  {
    defaults: {
      params: {},
      requestOptions: {},
      showHeader: null,
      closeOnSubmit: true,
    },
  }
);
