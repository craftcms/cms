/** global: Craft */
/** global: Garnish */
/**
 * Structure Select input
 */
Craft.StructureSelectInput = Craft.BaseElementSelectInput.extend(
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

            // Get the new element HTML
            var selectedElementIds = this.getSelectedElementIds();

            for (var i = 0; i < elements.length; i++) {
                selectedElementIds.push(elements[i].id);
            }

            var data = {
                elementIds: selectedElementIds,
                siteId: elements[0].siteId,
                id: this.settings.id,
                name: this.settings.name,
                branchLimit: this.settings.branchLimit,
                selectionLabel: this.settings.selectionLabel,
                elementType: this.settings.elementType
            };

            Craft.postActionRequest('elements/get-structure-input-html', data, $.proxy(function(response, textStatus) {
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
            // Find any descendants this element might have
            var $allElements = $element.add($element.parent().siblings('ul').find('.element'));

            // Remove our record of them all at once
            this.removeElements($allElements);

            // Animate them away one at a time
            for (var i = 0; i < $allElements.length; i++) {
                this._animateElementAway($allElements, i);
            }
        },

        _animateElementAway: function($allElements, i) {
            var callback;

            // Is this the last one?
            if (i === $allElements.length - 1) {
                callback = $.proxy(function() {
                    var $li = $allElements.first().parent().parent(),
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
                this.animateElementAway($allElements.eq(i), callback);
            }, this);

            if (i === 0) {
                func();
            }
            else {
                setTimeout(func, 100 * i);
            }
        }
    });
