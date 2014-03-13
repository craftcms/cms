/**
 * Tag select input
 */
Craft.TagSelectInput = Craft.BaseElementSelectInput.extend(
{
	id: null,
	name: null,
	tagGroupId: null,
	sourceElementId: null,
	elementSort: null,
	searchTimeout: null,
	searchMenu: null,

	$container: null,
	$elementsContainer: null,
	$elements: null,
	$addTagInput: null,
	$spinner: null,

	init: function(id, name, tagGroupId, sourceElementId)
	{
		this.id = id;
		this.name = name;
		this.tagGroupId = tagGroupId;
		this.sourceElementId = sourceElementId;

		this.$container = $('#'+this.id);
		this.$elementsContainer = this.$container.children('.elements');
		this.$elements = this.$elementsContainer.children();
		this.$addTagInput = this.$container.children('.add').children('.text');
		this.$spinner = this.$addTagInput.next();

		this.totalElements = this.$elements.length;

		this.elementSelect = new Garnish.Select(this.$elements, {
			multi: true,
			filter: ':not(.delete)'
		});

		this.elementSort = new Garnish.DragSort({
			container: this.$elementsContainer,
			filter: $.proxy(function() {
				return this.elementSelect.getSelectedItems();
			}, this),
			caboose: $('<div class="caboose"/>'),
			onSortChange: $.proxy(function() {
				this.elementSelect.resetItemOrder();
			}, this)
		});

		this.initElements(this.$elements);

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
			setTimeout($.proxy(function()
			{
				if (this.searchMenu)
				{
					this.searchMenu.hide();
				}
			}, this), 1);
		});
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

			if (this.sourceElementId)
			{
				excludeIds.push(this.sourceElementId);
			}

			var data = {
				search:     this.$addTagInput.val(),
				tagGroupId: this.tagGroupId,
				excludeIds: excludeIds
			};

			Craft.postActionRequest('tags/searchForTags', data, $.proxy(function(response, textStatus)
			{
				this.$spinner.addClass('hidden');

				if (textStatus == 'success')
				{
					var $menu = $('<div class="menu tagmenu"/>').appendTo(Garnish.$bod),
						$ul = $('<ul/>').appendTo($menu);

					for (var i = 0; i < response.tags.length; i++)
					{
						var $li = $('<li/>').appendTo($ul);
						$('<a data-icon="tag"/>').appendTo($li).text(response.tags[i].name).data('id', response.tags[i].id);
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
			name = $option.text();

		var $element = $('<div class="element removable" data-id="'+id+'" data-editable="1"/>').appendTo(this.$elementsContainer),
			$input = $('<input type="hidden" name="'+this.name+'[]" value="'+id+'"/>').appendTo($element)

		$('<a class="delete icon" title="'+Craft.t('Remove')+'"></a>').appendTo($element);
		$('<span class="label">'+name+'</span>').appendTo($element);

		var margin = -($element.outerWidth()+10);
		this.$addTagInput.css('margin-'+Craft.left, margin+'px');

		var animateCss = {};
		animateCss['margin-'+Craft.left] = 0;
		this.$addTagInput.animate(animateCss, 'fast');

		this.$elements = this.$elements.add($element);
		this.totalElements++;

		this.initElements($element);

		this.killSearchMenu();
		this.$addTagInput.val('');
		this.$addTagInput.focus();

		if (!id)
		{
			// We need to create the tag first
			$element.addClass('loading disabled');

			var data = {
				groupId: this.tagGroupId,
				name: name
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

});
