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
    $actionBtn: null,
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

    /**
     * @returns {boolean} Whether the slideout is wide enough to show the sidebar alongside the content
     */
    get showExpandedView() {
      return this.$container.width() > 700;
    },

    get sidebarIsOverlapping() {
      return (
        this.showingSidebar && this.$sidebar.css('position') === 'absolute'
      );
    },

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
        title: Craft.t('app', 'Open in a new tab'),
        'aria-label': Craft.t('app', 'Open in a new tab'),
        'data-icon': 'external',
      }).appendTo(this.$toolbar);
      this.$sidebarBtn = $('<button/>', {
        type: 'button',
        class: 'btn header-btn hidden sidebar-btn',
        title: Craft.t('app', 'Show sidebar'),
        'aria-label': Craft.t('app', 'Show sidebar'),
        'data-icon': `sidebar-${Garnish.ltr ? 'right' : 'left'}`,
        'aria-expanded': 'false',
      }).appendTo(this.$toolbar);

      this.addListener(this.$sidebarBtn, 'click', (ev) => {
        ev.preventDefault();
        if (!this.showingSidebar) {
          this.showSidebar();
          if (this.showExpandedView) {
            Craft.setCookie('sidebar-slideout', 'expanded');
          }
        } else {
          this.hideSidebar();
          if (this.showExpandedView) {
            Craft.setCookie('sidebar-slideout', 'collapsed');
          }
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
        this.hideSidebarIfOverlapping();
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
          this.hideSidebarIfOverlapping();
        }
      });
      this.addListener(this.$container, 'submit', 'handleSubmit');

      this.load();
    },

    /**
     * @param {Object} [data={}]
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
     * @param {Object} data
     * @returns {Promise}
     */
    update: function (data) {
      return new Promise((resolve) => {
        this.namespace = data.namespace;

        if (data.bodyClass) {
          this.$body.addClass(data.bodyClass);
        }

        this.$content.html(data.content);

        if (data.submitButtonLabel) {
          this.$saveBtn.text(data.submitButtonLabel);
        }

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

        if (data.actionMenu) {
          const labelId = Craft.namespaceId(
            'action-menu-label',
            this.namespace
          );
          const menuId = Craft.namespaceId('action-menu', this.namespace);
          $('<label/>', {
            id: labelId,
            class: 'visually-hidden',
            text: Craft.t('app', 'Actions'),
          }).insertBefore(this.$editLink);
          this.$actionBtn = $('<button/>', {
            class: 'btn action-btn header-btn',
            type: 'button',
            title: Craft.t('app', 'Actions'),
            'aria-controls': menuId,
            'aria-describedby': labelId,
            'data-disclosure-trigger': 'true',
          }).insertBefore(this.$editLink);
          $(data.actionMenu).insertBefore(this.$editLink);
          this.$actionBtn.disclosureMenu();
        } else {
          this.$actionBtn = null;
        }

        if (data.sidebar) {
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

          if (
            this.showExpandedView &&
            (Craft.getCookie('sidebar-slideout') || 'expanded') === 'expanded'
          ) {
            this.showSidebar(false);
          } else {
            this.hideSidebar();
          }
        } else {
          this.hideSidebar();
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

        Garnish.requestAnimationFrame(async () => {
          Craft.initUiElements(this.$content);
          await Craft.appendHeadHtml(data.headHtml);
          await Craft.appendBodyHtml(data.bodyHtml);
          Craft.cp.elementThumbLoader.load($(this.$content));

          if (data.sidebar) {
            Craft.initUiElements(this.$sidebar);
            Craft.cp.elementThumbLoader.load(this.$sidebar);
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

    showSidebar: function (focus = true) {
      if (this.showingSidebar) {
        return;
      }

      this.$container.addClass('showing-sidebar');
      this.$body.scrollTop(0).addClass('no-scroll');

      this.$sidebar
        .off('transitionend.so')
        .css(this._closedSidebarStyles())
        .removeClass('hidden');

      // Hack to force CSS animations
      this.$sidebar[0].offsetWidth;

      this.$sidebar.css(this._openedSidebarStyles());

      if (focus && !Garnish.isMobileBrowser()) {
        this.$sidebar.one('transitionend.so', () => {
          Craft.setFocusWithin(this.$sidebar);
        });
      }

      if (!this.showExpandedView) {
        Craft.trapFocusWithin(this.$sidebar);
      }

      this.$sidebarBtn.addClass('active').attr({
        'aria-expanded': 'true',
      });

      Garnish.$win.trigger('resize');
      this.$sidebar.trigger('scroll');

      Garnish.uiLayerManager.addLayer({
        bubble: true,
      });
      Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, (ev) => {
        this.hideSidebarIfOverlapping() || ev.bubbleShortcut();
      });

      this.showingSidebar = true;
    },

    hideSidebar: function () {
      if (!this.showingSidebar) {
        return;
      }

      this.$container.removeClass('showing-sidebar');
      this.$body.removeClass('no-scroll');

      // Do the same thing when there are no transitions
      if (!this.sidebarIsOverlapping) {
        this.$sidebar.addClass('hidden');
        this.$sidebarBtn.focus();
      }

      this.$sidebar
        .off('transitionend.so')
        .css(this._closedSidebarStyles())
        .one('transitionend.so', () => {
          this.$sidebar.addClass('hidden');
          this.$sidebarBtn.focus();
        });

      Craft.releaseFocusWithin(this.$sidebar);

      this.$sidebarBtn.removeClass('active').attr({
        'aria-expanded': 'false',
      });

      Garnish.uiLayerManager.removeLayer();

      this.showingSidebar = false;
    },

    hideSidebarIfOverlapping() {
      if (this.sidebarIsOverlapping) {
        this.hideSidebar();
        return true;
      } else {
        return false;
      }
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
      // give other submit handlers a chance to modify things
      setTimeout(() => {
        this.submit();
      }, 1);
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
        .catch((e) => {
          this.handleSubmitError(e);
        })
        .finally(() => {
          this.hideSubmitSpinner();
        });
    },

    handleSubmitResponse: function (response) {
      this.clearErrors();
      const data = response.data || {};
      if (data.message) {
        Craft.cp.displaySuccess(data.message, data.notificationSettings);
      }
      if (data.modelClass && data.modelId) {
        Craft.refreshComponentInstances(data.modelClass, data.modelId);
      }
      const ev = {
        response: response,
        data: (data.modelName && data[data.modelName]) || {},
      };
      this.trigger('submit', ev);
      this.settings.onSubmit(ev);
      if (this.settings.closeOnSubmit) {
        this.close();
      }
    },

    handleSubmitError: function (e) {
      if (!e.isAxiosError || !e.response || !e.response.status === 400) {
        Craft.cp.displayError();
        throw e;
      }

      const data = e.response.data || {};
      Craft.cp.displayError(data.message);
      if (data.errors) {
        this.showErrors(data.errors);
      }

      if (data.errorSummary) {
        this.showErrorSummary(
          data.errorSummary,
          Object.keys(data.errors || {}).length
        );
      }
    },

    showErrorSummary: function (errorSummary, errorCount = 0) {
      // start by clearing any error summary that might be left
      Craft.ui.clearErrorSummary(this.$body);

      // if we have multiple tabs - split the error summary into them
      if (this.tabManager !== null) {
        let $tabs = this.tabManager.$tabs;
        let $tabsWithErrors = $tabs.filter('.error');
        let $content = this.$content;

        $tabs.each(function (i, tab) {
          let tabDataId = $(tab).data('id');
          let $tabContainer = $content.find('#' + tabDataId);
          if ($tabContainer.length > 0) {
            let tabUid = $tabContainer.data('layout-tab');
            let $tabErrorSummary = $(errorSummary);
            let tabErrorCount = $tabErrorSummary.find('ul.errors li').length;
            let headingText = '';

            // remove any errors that are not specifically for this tab
            // leave out errors that don't have a tab assignment (e.g. cross-validation errors)
            $tabErrorSummary.find('ul.errors li').each(function (j, error) {
              let errorTabUid = $(error).find('a').data('layout-tab');
              if (
                typeof errorTabUid !== 'undefined' &&
                errorTabUid !== tabUid
              ) {
                $(error).remove();
                tabErrorCount--;
              }
            });

            if (tabErrorCount > 0) {
              headingText = Craft.t(
                'app',
                'Found {num, number} {num, plural, =1{error} other{errors}} in this tab.',
                {num: tabErrorCount}
              );

              // if there are errors in any other tabs - tell users about it.
              if ($tabsWithErrors.length - 1 > 0) {
                headingText +=
                  '<span class="visually-hidden">' +
                  Craft.t(
                    'app',
                    '{total, number} {total, plural, =1{error} other{errors}} found in {num, number} {num, plural, =1{tab} other{tabs}}.',
                    {
                      total: errorCount,
                      num: $tabsWithErrors.length,
                    }
                  ) +
                  '</span>';
              }
            } else {
              headingText = Craft.t('app', 'Found errors in other tabs.');
            }

            $tabErrorSummary.find('h2').html(headingText);

            $tabErrorSummary.prependTo($tabContainer);
            Craft.ui.setFocusOnErrorSummary($tabContainer); // this also makes the deep linking work
          }
        });
      } else {
        // if we only have one tab - just show the error summary as is
        $(errorSummary).prependTo(this.$content);
        Craft.ui.setFocusOnErrorSummary(this.$content);
      }
    },

    /**
     * @param {string[]} errors
     */
    showErrors: function (errors) {
      this.clearErrors();

      const tabMenu = this.tabManager?.menu || [];
      const tabErrorIndicator =
        '<span data-icon="alert">' +
        '<span class="visually-hidden">' +
        Craft.t('app', 'This tab contains errors') +
        '</span>\n' +
        '</span>';

      Object.entries(errors).forEach(([name, fieldErrors]) => {
        const $field = this.$container.find(`[data-error-key="${name}"]`);
        if ($field) {
          Craft.ui.addErrorsToField($field, fieldErrors);
          this.fieldsWithErrors.push($field);

          // find tabs that contain fields with errors
          let fieldTabAnchors = Craft.ui.findTabAnchorForField(
            $field,
            this.$container
          );

          // add error indicator to tabs
          if (fieldTabAnchors.length > 0) {
            // add error indicator to the tabs menuBtn
            if (this.tabManager.$menuBtn.hasClass('error') == false) {
              this.tabManager.$menuBtn.addClass('error');
              this.tabManager.$menuBtn.append(
                '<span data-icon="alert"></span>'
              );
            }

            for (let i = 0; i < fieldTabAnchors.length; i++) {
              let $fieldTabAnchor = $(fieldTabAnchors[i]);

              if ($fieldTabAnchor.hasClass('error') == false) {
                $fieldTabAnchor.addClass('error');
                $fieldTabAnchor.find('.tab-label').append(tabErrorIndicator);

                // also add the error indicator to the disclosure menu for the tabs
                if (tabMenu.length) {
                  let $tabMenuItem = tabMenu.find(
                    '[data-id=' + $fieldTabAnchor.data('id') + ']'
                  );
                  if (
                    $tabMenuItem.length > 0 &&
                    $tabMenuItem.hasClass('error') == false
                  ) {
                    $tabMenuItem.addClass('error');
                    $tabMenuItem.append(tabErrorIndicator);
                  }
                }
              }
            }
          }
        }
      });
    },

    clearErrors: function () {
      this.fieldsWithErrors.forEach(($field) => {
        Craft.ui.clearErrorsFromField($field);
      });
    },

    isDirty: function () {
      const initialValue = this.$container.data('initialSerializedValue');
      if (typeof initialValue === 'undefined') {
        return false;
      }

      const serializer =
        this.$container.data('serializer') ||
        (() => this.$container.serialize());
      return initialValue !== serializer();
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
      if (this.showingSidebar) {
        this.hideSidebar();
      }

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
      onSubmit: () => {},
    },
  }
);
