/** global: Craft */
/** global: Garnish */
/**
 * Base Element Index View
 */
Craft.BaseElementIndexView = Garnish.Base.extend(
  {
    $container: null,
    $loadingMoreSpinner: null,
    $elementContainer: null,
    $scroller: null,

    elementIndex: null,
    elementSelect: null,

    loadingMore: false,

    _totalVisible: null,
    _morePending: null,
    _handleEnableElements: null,
    _handleDisableElements: null,

    get thumbLoader() {
      console.warn(
        'Craft.BaseElementIndexView::thumbLoader is deprecated. Craft.cp.elementThumbLoader should be used instead.'
      );
      return Craft.cp.elementThumbLoader;
    },

    init: function (elementIndex, container, settings) {
      this.elementIndex = elementIndex;
      this.$container = $(container);
      this.setSettings(settings, Craft.BaseElementIndexView.defaults);

      // Create a "loading-more" spinner
      this.$loadingMoreSpinner = $(
        '<div class="centeralign hidden">' +
          '<div class="spinner loadingmore"></div>' +
          '</div>'
      ).insertAfter(this.$container);

      // Get the actual elements container and its child elements
      this.$elementContainer = this.getElementContainer();
      var $elements = this.$elementContainer.children();

      this.setTotalVisible($elements.length);
      this.setMorePending(
        this.elementIndex.settings.batchSize &&
          $elements.length == this.elementIndex.settings.batchSize
      );

      // Load thumbnails
      Craft.cp.elementThumbLoader.load($elements);

      if (this.settings.selectable) {
        this.elementSelect = new Garnish.Select(
          this.$elementContainer,
          this.filterSelectableElements($elements),
          {
            multi: this.settings.multiSelect,
            vertical: this.isVerticalList(),
            filter: (target) => {
              return !$(target).closest('a[href],.toggle,.btn,[role=button]')
                .length;
            },
            checkboxMode: this.settings.checkboxMode,
            waitForDoubleClicks: this.settings.waitForDoubleClicks,
            onSelectionChange: this.onSelectionChange.bind(this),
          }
        );

        this._handleEnableElements = (ev) => {
          this.elementSelect.addItems(
            this.filterSelectableElements($(ev.elements))
          );
        };

        this._handleDisableElements = (ev) => {
          this.elementSelect.removeItems(ev.elements);
        };

        this.elementIndex.on('enableElements', this._handleEnableElements);
        this.elementIndex.on('disableElements', this._handleDisableElements);
      }

      // Enable inline element editing if this is an index page
      if (this.elementIndex.isAdministrative) {
        this._handleElementEditing = (ev) => {
          if (
            this.elementIndex.inlineEditing ||
            $(ev.target).closest('a[href],button,[role=button]').length
          ) {
            // Let the link/button do its thing
            return;
          }

          const $target = $(ev.target);
          let $element = $target.closest('.element');
          if (!$element.length) {
            $element = $target.closest('tr').find('.element:first');
            if (!$element.length) {
              return;
            }
          }

          if (
            Garnish.hasAttr($element, 'data-editable') &&
            !$element.closest('.elementselect').length
          ) {
            Craft.createElementEditor($element.data('type'), $element);
          }
        };

        if (!this.elementIndex.trashed) {
          this.addListener(
            this.$elementContainer,
            'dblclick,taphold',
            this._handleElementEditing
          );
        }
      }

      // Give sub-classes a chance to do post-initialization stuff here
      this.afterInit();

      // Set up $scroller for reordering
      if (
        (!this.elementIndex.paginated ||
          this.elementIndex.settings.context === 'embedded-index') &&
        this.elementIndex.settings.batchSize
      ) {
        if (this.settings.context === 'index') {
          this.$scroller = Garnish.$scrollContainer;
        } else {
          this.$scroller = this.elementIndex.$main;
        }

        this.$scroller.scrollTop(0);

        // and if we're not in a paginated view, set up lazy-loading
        if (!this.elementIndex.paginated) {
          this.addListener(this.$scroller, 'scroll', 'maybeLoadMore');
          this.maybeLoadMore();
        }
      }
    },

    filterSelectableElements: function ($elements) {
      const selectable = [];

      for (let i = 0; i < $elements.length; i++) {
        const $element = $elements.eq(i);
        if ($element.hasClass('disabled')) {
          // remove checkbox from tab order and mark as checked
          $element.find('.checkbox').attr({
            tabindex: '-1',
            'aria-checked': 'true',
          });
          continue;
        }
        if (this.canSelectElement($element)) {
          selectable.push($element[0]);
        } else {
          // make sure it doesn't have a checkbox
          $element.find('.checkbox').remove();
        }
      }

      return $(selectable);
    },

    canSelectElement: function ($element) {
      if (this.settings.canSelectElement) {
        return this.settings.canSelectElement($element);
      }
      return !!$element.data('id');
    },

    getElementContainer: function () {
      return this.$container;
    },

    afterInit: function () {},

    getAllElements: function () {
      return this.$elementContainer.children();
    },

    getEnabledElements: function () {
      return this.$elementContainer.children(':not(.disabled)');
    },

    getElementById: function (id) {
      var $element = this.$elementContainer.children(
        '[data-id="' + id + '"]:first'
      );

      if ($element.length) {
        return $element;
      } else {
        return null;
      }
    },

    getSelectedElements: function () {
      if (!this.elementSelect) {
        throw 'This view is not selectable.';
      }

      return this.elementSelect.$selectedItems;
    },

    getSelectedElementIds: function () {
      let $selectedElements;
      try {
        $selectedElements = this.getSelectedElements();
      } catch (e) {}

      let ids = [];
      if ($selectedElements) {
        for (var i = 0; i < $selectedElements.length; i++) {
          const id = $selectedElements.eq(i).data('id');
          if (id) {
            ids.push(id);
          }
        }
      }
      return ids;
    },

    selectElement: function ($element) {
      if (!this.elementSelect) {
        throw 'This view is not selectable.';
      }

      this.elementSelect.selectItem($element, true);
      return true;
    },

    selectElementById: function (id) {
      if (!this.elementSelect) {
        throw 'This view is not selectable.';
      }

      var $element = this.getElementById(id);

      if ($element) {
        this.elementSelect.selectItem($element, true);
        return true;
      } else {
        return false;
      }
    },

    selectAllElements: function () {
      this.elementSelect.selectAll();
    },

    deselectAllElements: function () {
      this.elementSelect.deselectAll();
    },

    getElementCheckbox: function (element) {
      return $(element).find('.checkbox');
    },

    isVerticalList: function () {
      return false;
    },

    getTotalVisible: function () {
      return this._totalVisible;
    },

    setTotalVisible: function (totalVisible) {
      this._totalVisible = totalVisible;
    },

    getMorePending: function () {
      return this._morePending;
    },

    setMorePending: function (morePending) {
      this._morePending = morePending;
    },

    /**
     * Checks if the user has reached the bottom of the scroll area, and if so, loads the next batch of elemets.
     */
    maybeLoadMore: function () {
      if (this.canLoadMore()) {
        this.loadMore();
      }
    },

    /**
     * Returns whether the user has reached the bottom of the scroll area.
     */
    canLoadMore: function () {
      if (!this.getMorePending() || !this.elementIndex.settings.batchSize) {
        return false;
      }

      // Check if the user has reached the bottom of the scroll area
      var containerHeight;

      if (this.$scroller[0] === Garnish.$win[0]) {
        var winHeight = Garnish.$win.innerHeight(),
          winScrollTop = Garnish.$win.scrollTop(),
          containerOffset = this.$container.offset().top;
        containerHeight = this.$container.height();

        return winHeight + winScrollTop >= containerOffset + containerHeight;
      } else {
        var containerScrollHeight = this.$scroller.prop('scrollHeight'),
          containerScrollTop = this.$scroller.scrollTop();
        containerHeight = this.$scroller.outerHeight();

        return (
          containerScrollHeight - containerScrollTop <= containerHeight + 15
        );
      }
    },

    /**
     * Loads the next batch of elements.
     */
    loadMore: function () {
      if (
        !this.getMorePending() ||
        this.loadingMore ||
        !this.elementIndex.settings.batchSize
      ) {
        return;
      }

      this.loadingMore = true;
      this.$loadingMoreSpinner.removeClass('hidden');
      this.removeListener(this.$scroller, 'scroll');

      Craft.sendActionRequest('POST', this.settings.loadMoreElementsAction, {
        data: this.getLoadMoreParams(),
      })
        .then(async (response) => {
          this.loadingMore = false;
          this.$loadingMoreSpinner.addClass('hidden');

          if (this.isAdministrative) {
            // set Craft.currentElementIndex for actions
            Craft.currentElementIndex = this;
          }

          let $newElements = $(response.data.html);

          if (this.elementIndex.selectable) {
            const role = this.elementIndex.multiSelect ? 'checkbox' : 'radio';
            $newElements.find('.checkbox').attr('role', role);
          }

          this.appendElements($newElements);
          await Craft.appendHeadHtml(response.data.headHtml);
          await Craft.appendBodyHtml(response.data.bodyHtml);

          if (this.elementSelect) {
            this.elementSelect.addItems(
              this.filterSelectableElements($newElements)
            );
            this.elementIndex.updateActionTriggers();
          }

          this.setTotalVisible(this.getTotalVisible() + $newElements.length);
          this.setMorePending(
            $newElements.length == this.elementIndex.settings.batchSize
          );

          // Is there room to load more right now?
          this.addListener(this.$scroller, 'scroll', 'maybeLoadMore');
          this.maybeLoadMore();
        })
        .catch((e) => {
          this.loadingMore = false;
          this.$loadingMoreSpinner.addClass('hidden');
        });
    },

    getLoadMoreParams: function () {
      // Use the same params that were passed when initializing this view
      var params = $.extend(true, {}, this.settings.params);
      params.criteria.offset = this.getTotalVisible();
      return params;
    },

    appendElements: function ($newElements) {
      $newElements.appendTo(this.$elementContainer);
      Craft.cp.elementThumbLoader.load($newElements);
      this.onAppendElements($newElements);
    },

    onAppendElements: function ($newElements) {
      this.settings.onAppendElements($newElements);
      this.trigger('appendElements', {
        newElements: $newElements,
      });
    },

    onSelectionChange: function () {
      this.settings.onSelectionChange();
      this.trigger('selectionChange');
    },

    disable: function () {
      if (this.elementSelect) {
        this.elementSelect.disable();
      }
    },

    enable: function () {
      if (this.elementSelect) {
        this.elementSelect.enable();
      }
    },

    destroy: function () {
      // Remove the "loading-more" spinner, since we added that outside of the view container
      this.$loadingMoreSpinner.remove();

      // Delete the element select
      if (this.elementSelect) {
        this.elementIndex.off('enableElements', this._handleEnableElements);
        this.elementIndex.off('disableElements', this._handleDisableElements);

        this.elementSelect.destroy();
        delete this.elementSelect;
      }

      this.base();
    },
  },
  {
    defaults: {
      context: 'index',
      batchSize: null,
      params: null,
      selectable: false,
      multiSelect: false,
      canSelectElement: null,
      checkboxMode: false,
      waitForDoubleClicks: false,
      sortable: false,
      loadMoreElementsAction: 'element-indexes/get-more-elements',
      onAppendElements: $.noop,
      onSelectionChange: $.noop,
      onSortChange: $.noop,
    },
  }
);
