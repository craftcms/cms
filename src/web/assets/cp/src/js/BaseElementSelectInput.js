/** global: Craft */
/** global: Garnish */
/**
 * Element Select input
 */
Craft.BaseElementSelectInput = Garnish.Base.extend(
    {
        thumbLoader: null,
        elementSelect: null,
        elementSort: null,
        modal: null,
        elementEditor: null,

        $container: null,
        $elementsContainer: null,
        $elements: null,
        $addElementBtn: null,

        _initialized: false,

        init: function(settings) {
            // Normalize the settings and set them
            // ---------------------------------------------------------------------

            // Are they still passing in a bunch of arguments?
            if (!$.isPlainObject(settings)) {
                // Loop through all of the old arguments and apply them to the settings
                var normalizedSettings = {},
                    args = ['id', 'name', 'elementType', 'sources', 'criteria', 'sourceElementId', 'limit', 'modalStorageKey', 'fieldId'];

                for (var i = 0; i < args.length; i++) {
                    if (typeof arguments[i] !== 'undefined') {
                        normalizedSettings[args[i]] = arguments[i];
                    }
                    else {
                        break;
                    }
                }

                settings = normalizedSettings;
            }

            this.setSettings(settings, Craft.BaseElementSelectInput.defaults);

            // Apply the storage key prefix
            if (this.settings.modalStorageKey) {
                this.modalStorageKey = 'BaseElementSelectInput.' + this.settings.modalStorageKey;
            }

            // No reason for this to be sortable if we're only allowing 1 selection
            if (this.settings.limit == 1) {
                this.settings.sortable = false;
            }

            this.$container = this.getContainer();

            // Store a reference to this class
            this.$container.data('elementSelect', this);

            this.$elementsContainer = this.getElementsContainer();
            this.$addElementBtn = this.getAddElementsBtn();

            if (this.$addElementBtn && this.settings.limit == 1) {
                this.$addElementBtn
                    .css('position', 'absolute')
                    .css('top', 0)
                    .css(Craft.left, 0);
            }

            this.thumbLoader = new Craft.ElementThumbLoader();

            this.initElementSelect();
            this.initElementSort();
            this.resetElements();

            if (this.$addElementBtn) {
                this.addListener(this.$addElementBtn, 'activate', 'showModal');
            }

            this._initialized = true;
        },

        get totalSelected() {
            return this.$elements.length;
        },

        getContainer: function() {
            return $('#' + this.settings.id);
        },

        getElementsContainer: function() {
            return this.$container.children('.elements');
        },

        getElements: function() {
            return this.$elementsContainer.children();
        },

        getAddElementsBtn: function() {
            return this.$container.children('.btn.add');
        },

        initElementSelect: function() {
            if (this.settings.selectable) {
                this.elementSelect = new Garnish.Select({
                    multi: this.settings.sortable,
                    filter: ':not(.delete)'
                });
            }
        },

        initElementSort: function() {
            if (this.settings.sortable) {
                this.elementSort = new Garnish.DragSort({
                    container: this.$elementsContainer,
                    filter: (this.settings.selectable ? $.proxy(function() {
                            // Only return all the selected items if the target item is selected
                            if (this.elementSort.$targetItem.hasClass('sel')) {
                                return this.elementSelect.getSelectedItems();
                            }
                            else {
                                return this.elementSort.$targetItem;
                            }
                        }, this) : null),
                    ignoreHandleSelector: '.delete',
                    axis: this.getElementSortAxis(),
                    collapseDraggees: true,
                    magnetStrength: 4,
                    helperLagBase: 1.5,
                    onSortChange: (this.settings.selectable ? $.proxy(function() {
                            this.elementSelect.resetItemOrder();
                        }, this) : null)
                });
            }
        },

        getElementSortAxis: function() {
            return (this.settings.viewMode === 'list' ? 'y' : null);
        },

        canAddMoreElements: function() {
            return (!this.settings.limit || this.$elements.length < this.settings.limit);
        },

        updateAddElementsBtn: function() {
            if (this.canAddMoreElements()) {
                this.enableAddElementsBtn();
            }
            else {
                this.disableAddElementsBtn();
            }
        },

        disableAddElementsBtn: function() {
            if (this.$addElementBtn && !this.$addElementBtn.hasClass('disabled')) {
                this.$addElementBtn.addClass('disabled');

                if (this.settings.limit == 1) {
                    if (this._initialized) {
                        this.$addElementBtn.velocity('fadeOut', Craft.BaseElementSelectInput.ADD_FX_DURATION);
                    }
                    else {
                        this.$addElementBtn.hide();
                    }
                }
            }
        },

        enableAddElementsBtn: function() {
            if (this.$addElementBtn && this.$addElementBtn.hasClass('disabled')) {
                this.$addElementBtn.removeClass('disabled');

                if (this.settings.limit == 1) {
                    if (this._initialized) {
                        this.$addElementBtn.velocity('fadeIn', Craft.BaseElementSelectInput.REMOVE_FX_DURATION);
                    }
                    else {
                        this.$addElementBtn.show();
                    }
                }
            }
        },

        resetElements: function() {
            if (this.$elements !== null) {
                this.removeElements(this.$elements);
            } else {
                this.$elements = $();
            }

            this.addElements(this.getElements());
        },

        addElements: function($elements) {
            this.thumbLoader.load($elements);

            if (this.settings.selectable) {
                this.elementSelect.addItems($elements);
            }

            if (this.settings.sortable) {
                this.elementSort.addItems($elements);
            }

            if (this.settings.editable) {
                this._handleShowElementEditor = $.proxy(function(ev) {
                    var $element = $(ev.currentTarget);
                    if (Garnish.hasAttr($element, 'data-editable') && !$element.hasClass('disabled') && !$element.hasClass('loading')) {
                        this.elementEditor = this.createElementEditor($element);
                    }
                }, this);

                this.addListener($elements, 'dblclick', this._handleShowElementEditor);

                if ($.isTouchCapable()) {
                    this.addListener($elements, 'taphold', this._handleShowElementEditor);
                }
            }

            $elements.find('.delete').on('click', $.proxy(function(ev) {
                this.removeElement($(ev.currentTarget).closest('.element'));
            }, this));

            this.$elements = this.$elements.add($elements);
            this.updateAddElementsBtn();
        },

        createElementEditor: function($element) {
            return Craft.createElementEditor(this.settings.elementType, $element);
        },

        removeElements: function($elements) {
            if (this.settings.selectable) {
                this.elementSelect.removeItems($elements);
            }

            if (this.modal) {
                var ids = [];

                for (var i = 0; i < $elements.length; i++) {
                    var id = $elements.eq(i).data('id');

                    if (id) {
                        ids.push(id);
                    }
                }

                if (ids.length) {
                    this.modal.elementIndex.enableElementsById(ids);
                }
            }

            // Disable the hidden input in case the form is submitted before this element gets removed from the DOM
            $elements.children('input').prop('disabled', true);

            this.$elements = this.$elements.not($elements);
            this.updateAddElementsBtn();

            this.onRemoveElements();
        },

        removeElement: function($element) {
            this.removeElements($element);
            this.animateElementAway($element, function() {
                $element.remove();
            });
        },

        animateElementAway: function($element, callback) {
            $element.css('z-index', 0);

            var animateCss = {
                opacity: -1
            };
            animateCss['margin-' + Craft.left] = -($element.outerWidth() + parseInt($element.css('margin-' + Craft.right)));

            if (this.settings.viewMode === 'list' || this.$elements.length === 0) {
                animateCss['margin-bottom'] = -($element.outerHeight() + parseInt($element.css('margin-bottom')));
            }

            $element.velocity(animateCss, Craft.BaseElementSelectInput.REMOVE_FX_DURATION, callback);
        },

        showModal: function() {
            // Make sure we haven't reached the limit
            if (!this.canAddMoreElements()) {
                return;
            }

            if (!this.modal) {
                this.modal = this.createModal();
            }
            else {
                this.modal.show();
            }
        },

        createModal: function() {
            return Craft.createElementSelectorModal(this.settings.elementType, this.getModalSettings());
        },

        getModalSettings: function() {
            return $.extend({
                closeOtherModals: false,
                storageKey: this.modalStorageKey,
                sources: this.settings.sources,
                criteria: this.settings.criteria,
                multiSelect: (this.settings.limit != 1),
                showSiteMenu: this.settings.showSiteMenu,
                disabledElementIds: this.getDisabledElementIds(),
                onSelect: $.proxy(this, 'onModalSelect')
            }, this.settings.modalSettings);
        },

        getSelectedElementIds: function() {
            var ids = [];

            for (var i = 0; i < this.$elements.length; i++) {
                ids.push(this.$elements.eq(i).data('id'));
            }

            return ids;
        },

        getDisabledElementIds: function() {
            var ids = this.getSelectedElementIds();

            if (this.settings.sourceElementId) {
                ids.push(this.settings.sourceElementId);
            }

            return ids;
        },

        onModalSelect: function(elements) {
            if (this.settings.limit) {
                // Cut off any excess elements
                var slotsLeft = this.settings.limit - this.$elements.length;

                if (elements.length > slotsLeft) {
                    elements = elements.slice(0, slotsLeft);
                }
            }

            this.selectElements(elements);
            this.updateDisabledElementsInModal();
        },

        selectElements: function(elements) {
            for (var i = 0; i < elements.length; i++) {
                var elementInfo = elements[i],
                    $element = this.createNewElement(elementInfo);

                this.appendElement($element);
                this.addElements($element);
                this.animateElementIntoPlace(elementInfo.$element, $element);
            }

            this.onSelectElements(elements);
        },

        createNewElement: function(elementInfo) {
            var $element = elementInfo.$element.clone();

            // Make a couple tweaks
            Craft.setElementSize($element, (this.settings.viewMode === 'large' ? 'large' : 'small'));
            $element.addClass('removable');
            $element.prepend('<input type="hidden" name="' + this.settings.name + '[]" value="' + elementInfo.id + '">' +
                '<a class="delete icon" title="' + Craft.t('app', 'Remove') + '"></a>');

            return $element;
        },

        appendElement: function($element) {
            $element.appendTo(this.$elementsContainer);
        },

        animateElementIntoPlace: function($modalElement, $inputElement) {
            var origOffset = $modalElement.offset(),
                destOffset = $inputElement.offset(),
                $helper = $inputElement.clone().appendTo(Garnish.$bod);

            $inputElement.css('visibility', 'hidden');

            $helper.css({
                position: 'absolute',
                zIndex: 10000,
                top: origOffset.top,
                left: origOffset.left
            });

            var animateCss = {
                top: destOffset.top,
                left: destOffset.left
            };

            $helper.velocity(animateCss, Craft.BaseElementSelectInput.ADD_FX_DURATION, function() {
                $helper.remove();
                $inputElement.css('visibility', 'visible');
            });
        },

        updateDisabledElementsInModal: function() {
            if (this.modal.elementIndex) {
                this.modal.elementIndex.disableElementsById(this.getDisabledElementIds());
            }
        },

        getElementById: function(id) {
            for (var i = 0; i < this.$elements.length; i++) {
                var $element = this.$elements.eq(i);

                if ($element.data('id') == id) {
                    return $element;
                }
            }
        },

        onSelectElements: function(elements) {
            this.trigger('selectElements', {elements: elements});
            this.settings.onSelectElements(elements);
        },

        onRemoveElements: function() {
            this.trigger('removeElements');
            this.settings.onRemoveElements();
        }
    },
    {
        ADD_FX_DURATION: 200,
        REMOVE_FX_DURATION: 200,

        defaults: {
            id: null,
            name: null,
            fieldId: null,
            elementType: null,
            sources: null,
            criteria: {},
            sourceElementId: null,
            viewMode: 'list',
            limit: null,
            showSiteMenu: false,
            modalStorageKey: null,
            modalSettings: {},
            onSelectElements: $.noop,
            onRemoveElements: $.noop,
            sortable: true,
            selectable: true,
            editable: true,
            editorSettings: {}
        }
    });
