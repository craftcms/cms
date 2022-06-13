/** global: Craft */
/** global: Garnish */
/**
 * Element selector modal class
 */
Craft.BaseElementSelectorModal = Garnish.Modal.extend(
  {
    elementType: null,
    elementIndex: null,

    supportSidebarToggleView: false,

    $body: null,
    $content: null,
    $footer: null,
    $selectBtn: null,
    $sidebar: null,
    $sources: null,
    $sourceToggles: null,
    $sidebarToggleBtn: null,
    $sidebarCloseBtn: null,
    $mainHeading: null,
    $main: null,
    $search: null,
    $elements: null,
    $tbody: null,
    $primaryButtons: null,
    $secondaryButtons: null,
    $cancelBtn: null,

    init: function (elementType, settings) {
      this.elementType = elementType;
      this.setSettings(settings, Craft.BaseElementSelectorModal.defaults);
      var $headingId = 'elementSelectorModalHeading-' + Date.now();

      // Build the modal
      var $container = $(
          '<div class="modal elementselectormodal" aria-labelledby="' +
            $headingId +
            '"></div>'
        ).appendTo(Garnish.$bod),
        $heading = $(
          '<h1 id="' +
            $headingId +
            '" class="visually-hidden">' +
            this.settings.modalTitle +
            '</h1>'
        ).appendTo($container),
        $body = $(
          '<div class="body"><div class="spinner big"></div></div>'
        ).appendTo($container);

      this.$footer = $('<div class="footer"/>').appendTo($container);

      if (this.settings.fullscreen) {
        $container.addClass('fullscreen');
        this.settings.minGutter = 0;
      }

      this.base($container, this.settings);

      this.$secondaryButtons = $(
        '<div class="buttons left secondary-buttons"/>'
      ).appendTo(this.$footer);
      this.$primaryButtons = $('<div class="buttons right"/>').appendTo(
        this.$footer
      );
      this.$cancelBtn = $('<button/>', {
        type: 'button',
        class: 'btn',
        text: Craft.t('app', 'Cancel'),
      }).appendTo(this.$primaryButtons);
      this.$selectBtn = Craft.ui
        .createSubmitButton({
          class: 'disabled',
          label: Craft.t('app', 'Select'),
          spinner: true,
        })
        .appendTo(this.$primaryButtons);

      this.$body = $body;

      this.addListener(this.$cancelBtn, 'activate', 'cancel');
      this.addListener(this.$selectBtn, 'activate', 'selectElements');
    },

    updateModalBottomPadding: function () {
      const footerHeight = this.$footer.outerHeight();
      const bottomPadding = parseInt(this.$container.css('padding-bottom'));

      if (footerHeight !== bottomPadding) {
        this.$container.css('padding-bottom', footerHeight);
      }
    },

    updateSidebarView: function () {
      if (!this.supportSidebarToggleView) return;

      if (this.sidebarShouldBeHidden()) {
        if (!this.$sidebarToggleBtn) this.buildSidebarToggleView();
      } else {
        if (this.$sidebarToggleBtn) this.resetView();
      }
    },

    sidebarShouldBeHidden: function () {
      const contentWidth = this.$container.outerWidth();
      return contentWidth < 550;
    },

    resetView: function () {
      if (this.$mainHeader) {
        this.$mainHeader.remove();
      }

      if (this.$sidebarHeader) {
        this.$sidebarHeader.remove();
      }

      this.$sidebarToggleBtn = null;
      this.$body.addClass('has-sidebar');
      this.$content.addClass('has-sidebar');
      this.$sidebar.removeClass('hidden');
    },

    buildSidebarToggleView: function () {
      if (this.$sidebarToggleBtn || !this.sidebarShouldBeHidden()) return;

      this.$sidebarHeader = $('<div class="sidebar-header"/>').prependTo(
        this.$sidebar
      );

      this.$sidebarCloseBtn = Craft.ui
        .createButton({
          class: 'nav-close close-btn',
        })
        .attr('aria-label', Craft.t('app', 'Close'))
        .removeClass('btn')
        .appendTo(this.$sidebarHeader);

      this.$mainHeader = $('<div class="main-header"/>').prependTo(this.$main);
      this.$mainHeading = $(
        `<h2 class="main-heading">${this.getActiveSourceName()}</h2>`
      ).appendTo(this.$mainHeader);

      const buttonConfig = {
        toggle: true,
        controls: 'modal-sidebar',
        class: 'nav-toggle',
      };
      this.$sidebarToggleBtn = Craft.ui
        .createButton(buttonConfig)
        .removeClass('btn')
        .attr('aria-label', Craft.t('app', 'Show sidebar'))
        .appendTo(this.$mainHeader);

      this.$sidebar.attr('id', 'modal-sidebar');

      this.closeSidebar();

      this.addListener(this.$sidebarToggleBtn, 'click', () => {
        this.toggleSidebar();
      });

      this.addListener(this.$sidebarCloseBtn, 'click', () => {
        this.toggleSidebar();
      });
    },

    sidebarIsOpen: function () {
      return this.$sidebarToggleBtn.attr('aria-expanded') === 'true';
    },

    toggleSidebar: function () {
      if (this.sidebarIsOpen()) {
        this.closeSidebar();
      } else {
        this.openSidebar();
      }
    },

    openSidebar: function () {
      this.$body.addClass('has-sidebar');
      this.$content.addClass('has-sidebar');
      this.$sidebar.removeClass('hidden');
      this.$sidebarToggleBtn.attr('aria-expanded', 'true');
      this.$sidebar.find(':focusable').first().focus();

      Garnish.uiLayerManager.addLayer(this.$sidebar);
      Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, () => {
        this.closeSidebar();

        // If the focus is currently inside the sidebar, refocus the toggle
        const $focusedEl = Garnish.getFocusedElement();
        if ($.contains(this.$sidebar.get(0), $focusedEl.get(0)))
          this.$sidebarToggleBtn.focus();
      });
    },

    closeSidebar: function () {
      if (!this.$sidebarToggleBtn) return;

      if (this.sidebarIsOpen()) {
        Garnish.uiLayerManager.removeLayer();
        this.$sidebar.addClass('hidden');
        this.$sidebarToggleBtn.attr('aria-expanded', 'false');
      }

      this.$body.removeClass('has-sidebar');
      this.$content.removeClass('has-sidebar');
    },

    getActiveSourceName: function () {
      return this.$sidebar.find('.sel').text();
    },

    onFadeIn: function () {
      if (!this.elementIndex) {
        this._createElementIndex();
      } else {
        // Auto-focus the Search box
        if (!Garnish.isMobileBrowser(true)) {
          this.elementIndex.$search.trigger('focus');
        }
      }

      this.base();
    },

    onSelectionChange: function () {
      this.updateSelectBtnState();
    },

    onSelectSource: function () {
      this.updateHeading();
      this.updateModalBottomPadding();
    },

    updateHeading: function () {
      if (!this.$mainHeading) return;

      this.$mainHeading.text(this.getActiveSourceName());
    },

    updateSelectBtnState: function () {
      if (this.$selectBtn) {
        if (this.elementIndex.getSelectedElements().length) {
          this.enableSelectBtn();
        } else {
          this.disableSelectBtn();
        }
      }
    },

    enableSelectBtn: function () {
      this.$selectBtn.removeClass('disabled');
    },

    disableSelectBtn: function () {
      this.$selectBtn.addClass('disabled');
    },

    enableCancelBtn: function () {
      this.$cancelBtn.removeClass('disabled');
    },

    disableCancelBtn: function () {
      this.$cancelBtn.addClass('disabled');
    },

    showFooterSpinner: function () {
      this.$selectBtn.addClass('loading');
    },

    hideFooterSpinner: function () {
      this.$selectBtn.removeClass('loading');
    },

    cancel: function () {
      if (!this.$cancelBtn.hasClass('disabled')) {
        this.hide();
      }
    },

    selectElements: function () {
      if (this.elementIndex && this.elementIndex.getSelectedElements().length) {
        // TODO: This code shouldn't know about views' elementSelect objects
        this.elementIndex.view.elementSelect.clearMouseUpTimeout();

        var $selectedElements = this.elementIndex.getSelectedElements(),
          elementInfo = this.getElementInfo($selectedElements);

        this.onSelect(elementInfo);

        if (this.settings.disableElementsOnSelect) {
          this.elementIndex.disableElements(
            this.elementIndex.getSelectedElements()
          );
        }

        if (this.settings.hideOnSelect) {
          this.hide();
        }
      }
    },

    getElementInfo: function ($selectedElements) {
      var info = [];

      for (var i = 0; i < $selectedElements.length; i++) {
        var $element = $($selectedElements[i]);
        var elementInfo = Craft.getElementInfo($element);

        info.push(elementInfo);
      }

      return info;
    },

    onShow: function () {
      this.updateSelectBtnState();

      // Add listeners again since they get removed during modal close
      this.addListener(Garnish.$win, 'resize', this.updateSidebarView);
      this.addListener(Garnish.$win, 'resize', this.updateModalBottomPadding);

      this.updateModalBottomPadding();
      this.updateSidebarView();
      this.base();
    },

    onHide: function () {
      this.closeSidebar();
      this.base();
    },

    onSelect: function (elementInfo) {
      this.settings.onSelect(elementInfo);
    },

    disable: function () {
      if (this.elementIndex) {
        this.elementIndex.disable();
      }

      this.base();
    },

    enable: function () {
      if (this.elementIndex) {
        this.elementIndex.enable();
      }

      this.base();
    },

    _createElementIndex: function () {
      // Get the modal body HTML based on the settings
      var data = {
        context: 'modal',
        elementType: this.elementType,
        sources: this.settings.sources,
      };

      if (
        this.settings.showSiteMenu !== null &&
        this.settings.showSiteMenu !== 'auto'
      ) {
        data.showSiteMenu = this.settings.showSiteMenu ? '1' : '0';
      }

      Craft.sendActionRequest('POST', this.settings.bodyAction, {data}).then(
        (response) => {
          this.$body.html(response.data.html);

          if (this.$body.has('.sidebar:not(.hidden)').length) {
            this.$body.addClass('has-sidebar');
            this.supportSidebarToggleView = true;
          }

          // Initialize the element index
          this.elementIndex = Craft.createElementIndex(
            this.elementType,
            this.$body,
            Object.assign(
              {
                context: 'modal',
                modal: this,
                storageKey: this.settings.storageKey,
                condition: this.settings.condition,
                criteria: this.settings.criteria,
                disabledElementIds: this.settings.disabledElementIds,
                selectable: true,
                multiSelect: this.settings.multiSelect,
                buttonContainer: this.$secondaryButtons,
                onSelectionChange: this.onSelectionChange.bind(this),
                onSelectSource: this.onSelectSource.bind(this),
                hideSidebar: this.settings.hideSidebar,
                defaultSiteId: this.settings.defaultSiteId,
                defaultSource: this.settings.defaultSource,
              },
              this.settings.indexSettings
            )
          );

          this.$main = this.elementIndex.$main;
          this.$sidebar = this.elementIndex.$sidebar;
          this.$content = this.$body.find('.content');

          this.updateSidebarView();
          this.updateModalBottomPadding();

          // Double-clicking or double-tapping should select the elements
          this.addListener(
            this.elementIndex.$elements,
            'doubletap',
            function (ev, touchData) {
              // Make sure the touch targets are the same
              // (they may be different if Command/Ctrl/Shift-clicking on multiple elements quickly)
              if (touchData.firstTap.target === touchData.secondTap.target) {
                this.selectElements();
              }
            }
          );
        }
      );
    },
  },
  {
    defaults: {
      fullscreen: false,
      resizable: true,
      storageKey: null,
      sources: null,
      condition: null,
      criteria: null,
      multiSelect: false,
      showSiteMenu: null,
      disabledElementIds: [],
      disableElementsOnSelect: false,
      hideOnSelect: true,
      modalTitle: Craft.t('app', 'Select element'),
      onCancel: $.noop,
      onSelect: $.noop,
      hideSidebar: false,
      defaultSiteId: null,
      defaultSource: null,
      bodyAction: 'element-selector-modals/body',
      indexSettings: {},
    },
  }
);
