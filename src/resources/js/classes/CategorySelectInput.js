/**
 * Category Select input
 */
Craft.CategorySelectInput = Craft.BaseElementSelectInput.extend(
{
	sortable: false,

	getModalSettings: function()
	{
		var settings = this.base();
		settings.hideOnSelect = false;
		return settings;
	},

	getElements: function()
	{
		return this.$elementsContainer.find('.element');
	},

	onModalSelect: function(elements)
	{
		// Disable the modal
		this.modal.disable();
		this.modal.disableCancelBtn();
		this.modal.disableSelectBtn();
		this.modal.showFooterSpinner();

		// Get the new category HTML
		var selectedCategoryIds = this.getSelectedElementIds();

		for (var i = 0; i < elements.length; i++)
		{
			selectedCategoryIds.push(elements[i].id);
		}

		var data = {
			categoryIds: selectedCategoryIds,
			locale:      elements[0].locale,
			id:          this.id,
			name:        this.name,
			limit:       this.limit,
		};

		Craft.postActionRequest('elements/getCategoriesInputHtml', data, $.proxy(function(response, textStatus)
		{
			this.modal.enable();
			this.modal.disableCancelBtn();
			this.modal.disableSelectBtn();
			this.modal.hideFooterSpinner();

			if (textStatus == 'success')
			{
				var $newInput = $(response.html),
					$newElementsContainer = $newInput.children('.elements');

				this.$elementsContainer.replaceWith($newElementsContainer);
				this.$elementsContainer = $newElementsContainer;
				this.resetElements();

				for (var i = 0; i < elements.length; i++)
				{
					var element = elements[i],
						$element = this.getElementById(element.id);

					if ($element)
					{
						this.animateElementIntoPlace(element.$element, $element);
					}
				}

				this.updateDisabledElementsInModal();
				this.modal.hide();
				this.onSelectElements();
			}
		}, this));
	},

	removeElement: function($element)
	{
		// Find any descendants this category might have
		var $allCategories = $element.add($element.parent().siblings('ul').find('.element'));

		// Remove our record of them all at once
		this.removeElements($allCategories);

		// Animate them away one at a time
		for (var i = 0; i < $allCategories.length; i++)
		{
			this._animateCategoryAway($allCategories, i);
		}
	},

	_animateCategoryAway: function($allCategories, i)
	{
		// Is this the last one?
		if (i == $allCategories.length - 1)
		{
			var callback = $.proxy(function()
			{
				var $li = $allCategories.first().parent().parent(),
					$ul = $li.parent();

				if ($ul[0] == this.$elementsContainer[0] || $li.siblings().length)
				{
					$li.remove();
				}
				else
				{
					$ul.remove();
				}
			}, this);
		}
		else
		{
			callback = null;
		}

		var func = $.proxy(function() {
			this.animateElementAway($allCategories.eq(i), callback);
		}, this);

		if (i == 0)
		{
			func();
		}
		else
		{
			setTimeout(func, 100 * i);
		}
	}
});
