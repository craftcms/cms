/**
 * Tag select input
 */
Craft.TagSelectInput = Craft.BaseElementSelectInput.extend(
{
	searchTimeout: null,
	searchMenu: null,

	$container: null,
	$elementsContainer: null,
	$elements: null,
	$addTagInput: null,
	$spinner: null,

	_ignoreBlur: false,

	init: function(settings)
	{
		// Normalize the settings
		// ---------------------------------------------------------------------

		// Are they still passing in a bunch of arguments?
		if (!$.isPlainObject(settings))
		{
			// Loop through all of the old arguments and apply them to the settings
			var normalizedSettings = {},
				args = ['id', 'name', 'tagGroupId', 'sourceElementId'];

			for (var i = 0; i < args.length; i++)
			{
				if (typeof arguments[i] != typeof undefined)
				{
					normalizedSettings[args[i]] = arguments[i];
				}
				else
				{
					break;
				}
			}

			settings = normalizedSettings;
		}

		this.base($.extend({}, Craft.TagSelectInput.defaults, settings));

		this.$addTagInput = this.$container.children('.add').children('.text');
		this.$spinner = this.$addTagInput.next();

		this.addListener(this.$addTagInput, 'textchange', $.proxy(function()
		{
			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			this.searchTimeout = setTimeout($.proxy(this, 'searchForTags'), 500);
		}, this));

		this.addListener(this.$addTagInput, 'keypress', function(ev)
		{
			if (ev.keyCode == Garnish.RETURN_KEY)
			{
				ev.preventDefault();

				if (this.searchMenu)
				{
					this.selectTag(this.searchMenu.$options[0]);
				}
			}
		});

		this.addListener(this.$addTagInput, 'focus', function()
		{
			if (this.searchMenu)
			{
				this.searchMenu.show();
			}
		});

		this.addListener(this.$addTagInput, 'blur', function()
		{
			if (this._ignoreBlur)
			{
				this._ignoreBlur = false;
				return;
			}

			setTimeout($.proxy(function()
			{
				if (this.searchMenu)
				{
					this.searchMenu.hide();
				}
			}, this), 1);
		});
	},

	// No "add" button
	getAddElementsBtn: $.noop,

	getElementSortAxis: function()
	{
		return null;
	},

	searchForTags: function()
	{
		if (this.searchMenu)
		{
			this.killSearchMenu();
		}

		var val = this.$addTagInput.val();

		if (val)
		{
			this.$spinner.removeClass('hidden');

			var excludeIds = [];

			for (var i = 0; i < this.$elements.length; i++)
			{
				var id = $(this.$elements[i]).data('id');

				if (id)
				{
					excludeIds.push(id);
				}
			}

			if (this.settings.sourceElementId)
			{
				excludeIds.push(this.settings.sourceElementId);
			}

			var data = {
				search:     this.$addTagInput.val(),
				tagGroupId: this.settings.tagGroupId,
				excludeIds: excludeIds
			};

			Craft.postActionRequest('tags/searchForTags', data, $.proxy(function(response, textStatus)
			{
				// Just in case
				if (this.searchMenu)
				{
					this.killSearchMenu();
				}

				this.$spinner.addClass('hidden');

				if (textStatus == 'success')
				{
					var $menu = $('<div class="menu tagmenu"/>').appendTo(Garnish.$bod),
						$ul = $('<ul/>').appendTo($menu);

					for (var i = 0; i < response.tags.length; i++)
					{
						var $li = $('<li/>').appendTo($ul);
						$('<a data-icon="tag"/>').appendTo($li).text(response.tags[i].title).data('id', response.tags[i].id);
					}

					if (!response.exactMatch)
					{
						var $li = $('<li/>').appendTo($ul);
						$('<a data-icon="+"/>').appendTo($li).text(data.search);
					}

					$ul.find('> li:first-child > a').addClass('hover');

					this.searchMenu = new Garnish.Menu($menu, {
						attachToElement: this.$addTagInput,
						onOptionSelect: $.proxy(this, 'selectTag')
					});

					this.addListener($menu, 'mousedown', $.proxy(function()
					{
						this._ignoreBlur = true;
					}, this));

					this.searchMenu.show();
				}

			}, this));
		}
		else
		{
			this.$spinner.addClass('hidden');
		}
	},

	selectTag: function(option)
	{
		var $option = $(option),
			id = $option.data('id'),
			title = $option.text();

		var $element = $('<div class="element small removable" data-id="'+id+'" data-editable/>').appendTo(this.$elementsContainer),
			$input = $('<input type="hidden" name="'+this.settings.name+'[]" value="'+id+'"/>').appendTo($element);

		$('<a class="delete icon" title="'+Craft.t('Remove')+'"></a>').appendTo($element);
		$('<span class="label"/>').text(title).appendTo($element);

		var margin = -($element.outerWidth()+10);
		this.$addTagInput.css('margin-'+Craft.left, margin+'px');

		var animateCss = {};
		animateCss['margin-'+Craft.left] = 0;
		this.$addTagInput.velocity(animateCss, 'fast');

		this.$elements = this.$elements.add($element);

		this.addElements($element);

		this.killSearchMenu();
		this.$addTagInput.val('');
		this.$addTagInput.trigger('focus');

		if (!id)
		{
			// We need to create the tag first
			$element.addClass('loading disabled');

			var data = {
				groupId: this.settings.tagGroupId,
				title: title
			};

			Craft.postActionRequest('tags/createTag', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success' && response.success)
				{
					$element.attr('data-id', response.id);
					$input.val(response.id);

					$element.removeClass('loading disabled');
				}
				else
				{
					this.removeElement($element);

					if (textStatus == 'success')
					{
						// Some sort of validation error that still resulted in  a 200 response. Shouldn't be possible though.
						Craft.cp.displayError(Craft.t('An unknown error occurred.'));
					}
				}
			}, this));
		}
	},

	killSearchMenu: function()
	{
		this.searchMenu.hide();
		this.searchMenu.destroy();
		this.searchMenu = null;
	}
},
{
	defaults: {
		tagGroupId: null
	}
});
