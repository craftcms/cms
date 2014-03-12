/**
 * Category index class
 */
Craft.CategoryIndex = Craft.BaseElementIndex.extend(
{
	groupId: null,
	structure: null,

	$noCats: null,
	$addCategoryForm: null,
	$addCategoryInput: null,
	$addCategorySpinner: null,

	getDefaultSourceKey: function()
	{
		if (this.settings.context == 'index' && typeof defaultGroupHandle != 'undefined')
		{
			for (var i = 0; i < this.$sources.length; i++)
			{
				var $source = $(this.$sources[i]);

				if ($source.data('handle') == defaultGroupHandle)
				{
					return $source.data('key');
				}
			}
		}

		return this.base();
	},

	onSelectSource: function()
	{
		if (this.settings.context == 'index' && typeof history != 'undefined')
		{
			var uri = 'categories/'+this.$source.data('handle');

			history.replaceState({}, '', Craft.getUrl(uri));
		}

		this.base();
	},

	getViewModesForSource: function()
	{
		return [
			{ mode: 'structure', title: Craft.t('Display hierarchically'), icon: 'structure' }
		];
	},

	onUpdateElements: function(append)
	{
		// Make sure it's not table view (for a search)
		if (this.getSelectedSourceState('mode') == 'structure')
		{
			this.$noCats = this.$elements.children('.nocats');
			this.$addCategoryForm = this.$elements.children('form');
			this.$addCategoryInput = this.$addCategoryForm.find('input[type=text]');
			this.$addCategorySpinner = this.$addCategoryForm.find('.spinner');

			this.structure = this.$elementContainer.data('structure');
			this.groupId = this.$addCategoryForm.data('group-id');

			this.addListener(this.$addCategoryForm, 'submit', 'onAddCategorySubmit');
		}

		this.initElements(this.$elementContainer.find('.element'));

		this.base(append);
	},

	initElements: function($elements)
	{
		if (this.settings.context == 'index')
		{
			this.addListener($elements, 'dblclick', function(ev)
			{
				Craft.showElementEditor($(ev.currentTarget));
			});

			this.addListener($elements.siblings('.delete'), 'click', 'onDeleteClick');
		}
	},

	onDeleteClick: function(ev)
	{
		var $element = $(ev.currentTarget).siblings('.element'),
			info = Craft.getElementInfo($element);

		if (confirm(Craft.t('Are you sure you want to delete “{name}” and its descendants?', { name: info.label })))
		{
			Craft.postActionRequest('categories/deleteCategory', { categoryId: info.id }, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					if (response.success)
					{
						this.structure.removeElement($element);
						Craft.cp.displayNotice(Craft.t('“{name}” deleted.', { name: info.label }));

						// Was that the last one?
						if (!this.$elementContainer.find('.element').not($element).length)
						{
							this.$noCats.removeClass('hidden');
						}
					}
					else
					{
						Craft.cp.displayError(Craft.t('Couldn’t delete “{name}”.', { name: info.label }));
					}
				}

			}, this));
		}
	},

	onAddCategorySubmit: function(ev)
	{
		ev.preventDefault();

		this.$addCategorySpinner.removeClass('hidden');

		var data = {
			title: this.$addCategoryInput.val(),
			groupId: this.groupId
		};

		Craft.postActionRequest('categories/createCategory', data, $.proxy(function(response, textStatus) {

			this.$addCategorySpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				this.$noCats.addClass('hidden');

				var $element = $('<div class="element" data-editable="1"' +
					'data-id="'+response.id+'" ' +
					'data-locale="'+Craft.locale+'" ' +
					'data-status="'+response.status+'" ' +
					'data-label="'+response.title+'" ' +
					'data-url="'+response.url+'">' +
					'<div class="label">' +
						'<span class="status '+response.status+'"></span>' +
						'<span class="title">'+response.title+'</span>' +
					'</div>' +
				'</div>');

				// Add it to the structure
				this.structure.addElement($element);

				// Add the delete button
				var $row = $element.parent();
				$('<a class="delete icon" title="'+Craft.t('Delete')+'"></a>').appendTo($row);

				// Initialize it
				this.initElements($element);

				// Clear out the "Add a Category" input
				this.$addCategoryInput.val('');

				// Animate the new category into place
				$element.css({ top: 24, left: -5 }).animate({ top: 0, left: 0 }, 'fast');
			}

		}, this));
	}

});

// Register it!
Craft.registerElementIndexClass('Category', Craft.CategoryIndex);
