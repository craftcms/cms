/** global: Craft */
/** global: Garnish */
/**
 * Base Element Index View
 */
Craft.BaseElementIndexView = Garnish.Base.extend({
    $container: null,
    $loadingMoreSpinner: null,
    $elementContainer: null,
    $scroller: null,

    elementIndex: null,
    thumbLoader: null,
    elementSelect: null,

    loadingMore: false,

    _totalVisible: null,
    _morePending: null,
    _handleEnableElements: null,
    _handleDisableElements: null,

    init: function(elementIndex, container, settings) {
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
        this.setMorePending(this.settings.batchSize && $elements.length == this.settings.batchSize);

        // Instantiate the thumb loader
        this.thumbLoader = new Craft.ElementThumbLoader();
        this.thumbLoader.load($elements);

        if (this.settings.selectable) {
            this.elementSelect = new Garnish.Select(
                this.$elementContainer,
                $elements.filter(':not(.disabled)'),
                {
                    multi: this.settings.multiSelect,
                    vertical: this.isVerticalList(),
                    handle: (this.settings.context === 'index' ? '.checkbox, .element:first' : null),
                    filter: ':not(a):not(.toggle)',
                    checkboxMode: this.settings.checkboxMode,
                    onSelectionChange: $.proxy(this, 'onSelectionChange')
                });

            this._handleEnableElements = $.proxy(function(ev) {
                this.elementSelect.addItems(ev.elements);
            }, this);

            this._handleDisableElements = $.proxy(function(ev) {
                this.elementSelect.removeItems(ev.elements);
            }, this);

            this.elementIndex.on('enableElements', this._handleEnableElements);
            this.elementIndex.on('disableElements', this._handleDisableElements);
        }

        // Enable inline element editing if this is an index page
        if (this.settings.context === 'index') {
            this._handleElementEditing = $.proxy(function(ev) {
                var $target = $(ev.target);

                if ($target.prop('nodeName') === 'A') {
                    // Let the link do its thing
                    return;
                }

                var $element;

                if ($target.hasClass('element')) {
                    $element = $target;
                } else {
                    $element = $target.closest('.element');

                    if (!$element.length) {
                        return;
                    }
                }

                if (Garnish.hasAttr($element, 'data-editable')) {
                    this.createElementEditor($element);
                }
            }, this);

            if (!this.elementIndex.trashed) {
                this.addListener(this.$elementContainer, 'dblclick', this._handleElementEditing);
                if ($.isTouchCapable()) {
                    this.addListener(this.$elementContainer, 'taphold', this._handleElementEditing);
                }
            }
        }

        // Give sub-classes a chance to do post-initialization stuff here
        this.afterInit();

        // Set up lazy-loading
        if (this.settings.batchSize) {
            if (this.settings.context === 'index') {
                this.$scroller = Garnish.$scrollContainer;
            } else {
                this.$scroller = this.elementIndex.$main;
            }

            this.$scroller.scrollTop(0);
            this.addListener(this.$scroller, 'scroll', 'maybeLoadMore');
            this.maybeLoadMore();
        }
    },

    getElementContainer: function() {
        throw 'Classes that extend Craft.BaseElementIndexView must supply a getElementContainer() method.';
    },

    afterInit: function() {
    },

    getAllElements: function() {
        return this.$elementContainer.children();
    },

    getEnabledElements: function() {
        return this.$elementContainer.children(':not(.disabled)');
    },

    getElementById: function(id) {
        var $element = this.$elementContainer.children('[data-id="' + id + '"]:first');

        if ($element.length) {
            return $element;
        } else {
            return null;
        }
    },

    getSelectedElements: function() {
        if (!this.elementSelect) {
            throw 'This view is not selectable.';
        }

        return this.elementSelect.$selectedItems;
    },

    getSelectedElementIds: function() {
        let $selectedElements;
        try {
            $selectedElements = this.getSelectedElements();
        } catch (e) {
        }

        let ids = [];
        if ($selectedElements) {
            for (var i = 0; i < $selectedElements.length; i++) {
                ids.push($selectedElements.eq(i).data('id'));
            }
        }
        return ids;
    },

    selectElement: function($element) {
        if (!this.elementSelect) {
            throw 'This view is not selectable.';
        }

        this.elementSelect.selectItem($element, true);
        return true;
    },

    selectElementById: function(id) {
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

    selectAllElements: function() {
        this.elementSelect.selectAll();
    },

    deselectAllElements: function() {
        this.elementSelect.deselectAll();
    },

    isVerticalList: function() {
        return false;
    },

    getTotalVisible: function() {
        return this._totalVisible;
    },

    setTotalVisible: function(totalVisible) {
        this._totalVisible = totalVisible;
    },

    getMorePending: function() {
        return this._morePending;
    },

    setMorePending: function(morePending) {
        this._morePending = morePending;
    },

    /**
     * Checks if the user has reached the bottom of the scroll area, and if so, loads the next batch of elemets.
     */
    maybeLoadMore: function() {
        if (this.canLoadMore()) {
            this.loadMore();
        }
    },

    /**
     * Returns whether the user has reached the bottom of the scroll area.
     */
    canLoadMore: function() {
        if (!this.getMorePending() || !this.settings.batchSize) {
            return false;
        }

        // Check if the user has reached the bottom of the scroll area
        var containerHeight;

        if (this.$scroller[0] === Garnish.$win[0]) {
            var winHeight = Garnish.$win.innerHeight(),
                winScrollTop = Garnish.$win.scrollTop(),
                containerOffset = this.$container.offset().top;
            containerHeight = this.$container.height();

            return (winHeight + winScrollTop >= containerOffset + containerHeight);
        } else {
            var containerScrollHeight = this.$scroller.prop('scrollHeight'),
                containerScrollTop = this.$scroller.scrollTop();
            containerHeight = this.$scroller.outerHeight();

            return (containerScrollHeight - containerScrollTop <= containerHeight + 15);
        }
    },

    /**
     * Loads the next batch of elements.
     */
    loadMore: function() {
        if (!this.getMorePending() || this.loadingMore || !this.settings.batchSize) {
            return;
        }

        this.loadingMore = true;
        this.$loadingMoreSpinner.removeClass('hidden');
        this.removeListener(this.$scroller, 'scroll');

        var data = this.getLoadMoreParams();

        Craft.postActionRequest(this.settings.loadMoreElementsAction, data, $.proxy(function(response, textStatus) {
            this.loadingMore = false;
            this.$loadingMoreSpinner.addClass('hidden');

            if (textStatus === 'success') {
                var $newElements = $(response.html);

                this.appendElements($newElements);
                Craft.appendHeadHtml(response.headHtml);
                Craft.appendFootHtml(response.footHtml);

                if (this.elementSelect) {
                    this.elementSelect.addItems($newElements.filter(':not(.disabled)'));
                    this.elementIndex.updateActionTriggers();
                }

                this.setTotalVisible(this.getTotalVisible() + $newElements.length);
                this.setMorePending($newElements.length == this.settings.batchSize);

                // Is there room to load more right now?
                this.addListener(this.$scroller, 'scroll', 'maybeLoadMore');
                this.maybeLoadMore();
            }
        }, this));
    },

    getLoadMoreParams: function() {
        // Use the same params that were passed when initializing this view
        var params = $.extend(true, {}, this.settings.params);
        params.criteria.offset = this.getTotalVisible();
        return params;
    },

    appendElements: function($newElements) {
        $newElements.appendTo(this.$elementContainer);
        this.thumbLoader.load($newElements);
        this.onAppendElements($newElements);
    },

    onAppendElements: function($newElements) {
        this.settings.onAppendElements($newElements);
        this.trigger('appendElements', {
            newElements: $newElements
        });
    },

    onSelectionChange: function() {
        this.settings.onSelectionChange();
        this.trigger('selectionChange');
    },

    createElementEditor: function($element) {
        Craft.createElementEditor($element.data('type'), $element, {
            elementIndex: this.elementIndex
        });
    },

    disable: function() {
        if (this.elementSelect) {
            this.elementSelect.disable();
        }
    },

    enable: function() {
        if (this.elementSelect) {
            this.elementSelect.enable();
        }
    },

    destroy: function() {
        // Remove the "loading-more" spinner, since we added that outside of the view container
        this.$loadingMoreSpinner.remove();

        // Kill the thumb loader
        this.thumbLoader.destroy();
        delete this.thumbLoader;

        // Delete the element select
        if (this.elementSelect) {
            this.elementIndex.off('enableElements', this._handleEnableElements);
            this.elementIndex.off('disableElements', this._handleDisableElements);

            this.elementSelect.destroy();
            delete this.elementSelect;
        }

        this.base();
    }
}, {
    defaults: {
        context: 'index',
        batchSize: null,
        params: null,
        selectable: false,
        multiSelect: false,
        checkboxMode: false,
        loadMoreElementsAction: 'element-indexes/get-more-elements',
        onAppendElements: $.noop,
        onSelectionChange: $.noop
    }
});
