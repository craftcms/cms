/** global: Craft */
/** global: Garnish */
/**
 * Category Select input
 */
Craft.CategorySelectInput = Craft.BaseElementSelectInput.extend(
    {
        setSettings: function() {
            this.base.apply(this, arguments);
            this.settings.sortable = false;
        },

        getModalSettings: function() {
            var settings = this.base();
            settings.hideOnSelect = false;
            return settings;
        },

        getElements: function() {
            return this.$elementsContainer.find('.element');
        },

        onModalSelect: function(elements) {
            // Disable the modal
            this.modal.disable();
            this.modal.disableCancelBtn();
            this.modal.disableSelectBtn();
            this.modal.showFooterSpinner();

            // Get the new category HTML
            var selectedCategoryIds = this.getSelectedElementIds();

            for (var i = 0; i < elements.length; i++) {
                selectedCategoryIds.push(elements[i].id);
            }

            var data = {
                categoryIds: selectedCategoryIds,
                siteId: elements[0].siteId,
                id: this.settings.id,
                name: this.settings.name,
                branchLimit: this.settings.branchLimit,
                selectionLabel: this.settings.selectionLabel
            };

            Craft.postActionRequest('elements/get-categories-input-html', data, $.proxy(function(response, textStatus) {
                this.modal.enable();
                this.modal.enableCancelBtn();
                this.modal.enableSelectBtn();
                this.modal.hideFooterSpinner();

                if (textStatus === 'success') {
                    var $newInput = $(response.html),
                        $newElementsContainer = $newInput.children('.elements');

                    this.$elementsContainer.replaceWith($newElementsContainer);
                    this.$elementsContainer = $newElementsContainer;
                    this.resetElements();

                    var filteredElements = [];

                    for (var i = 0; i < elements.length; i++) {
                        var element = elements[i],
                            $element = this.getElementById(element.id);

                        if ($element) {
                            this.animateElementIntoPlace(element.$element, $element);
                            filteredElements.push(element);
                        }
                    }

                    this.updateDisabledElementsInModal();
                    this.modal.hide();
                    this.onSelectElements(filteredElements);
                }
            }, this));
        },

        removeElement: function($element) {
            // Find any descendants this category might have
            var $allCategories = $element.add($element.parent().siblings('ul').find('.element'));

            // Remove our record of them all at once
            this.removeElements($allCategories);

            // Animate them away one at a time
            for (var i = 0; i < $allCategories.length; i++) {
                this._animateCategoryAway($allCategories, i);
            }
        },

        _animateCategoryAway: function($allCategories, i) {
            var callback;

            // Is this the last one?
            if (i === $allCategories.length - 1) {
                callback = $.proxy(function() {
                    var $li = $allCategories.first().parent().parent(),
                        $ul = $li.parent();

                    if ($ul[0] === this.$elementsContainer[0] || $li.siblings().length) {
                        $li.remove();
                    }
                    else {
                        $ul.remove();
                    }
                }, this);
            }

            var func = $.proxy(function() {
                this.animateElementAway($allCategories.eq(i), callback);
            }, this);

            if (i === 0) {
                func();
            }
            else {
                setTimeout(func, 100 * i);
            }
        }
    });
