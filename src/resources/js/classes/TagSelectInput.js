/**
 * Tag select input
 */
Craft.TagSelectInput = Craft.BaseElementSelectInput.extend({

	id: null,
	name: null,
	tagSetId: null,
	sourceElementId: null,
	elementSort: null,
	searchTimeout: null,
	menu: null,

	$container: null,
	$elementsContainer: null,
	$elements: null,
	$addTagInput: null,
	$spinner: null,

	init: function(id, name, tagSetId, sourceElementId, hasFields)
	{
		this.id = id;
		this.name = name;
		this.tagSetId = tagSetId;
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

		if (hasFields)
		{
			this._attachHUDEvents();
		}
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
				tagSetId:   this.tagSetId,
				excludeIds: excludeIds
			};

			Craft.postActionRequest('tags/searchForTags', data, $.proxy(function(response, textStatus) {

				this.$spinner.addClass('hidden');

				if (textStatus == 'success')
				{
					var $menu = $('<div class="menu tagmenu"/>').appendTo(Garnish.$bod),
						$ul = $('<ul/>').appendTo($menu);

					if (!response.exactMatch)
					{
						var $li = $('<li/>').appendTo($ul);
						$('<a class="hover"/>').appendTo($li).text(data.search);
					}

					for (var i = 0; i < response.tags.length; i++)
					{
						var $li = $('<li/>').appendTo($ul),
							$a = $('<a/>').appendTo($li).text(response.tags[i].name).data('id', response.tags[i].id);

						if (response.exactMatch && i == 0)
						{
							$a.addClass('hover');
						}
					}

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
		var $option = $(option);

		var $element = $('<div class="element removable"/>').appendTo(this.$elementsContainer),
			$input = $('<input type="hidden" name="'+this.name+'[]"/>').appendTo($element)

		if ($option.data('id'))
		{
			$element.data('id', $option.data('id'));
			$input.val($option.data('id'));
		}
		else
		{
			$input.val('new:'+$option.text());
		}

		$('<a class="delete icon" title="'+Craft.t('Remove')+'"></a>').appendTo($element);
		$('<span class="label">'+$option.text()+'</span>').appendTo($element);

		var margin = -($element.outerWidth()+10);
		this.$addTagInput.css('margin-left', margin+'px');
		this.$addTagInput.animate({
			marginLeft: 0
		}, 'fast');

		this.$elements = this.$elements.add($element);
		this.totalElements++;

		this.initElements($element);

		this.killSearchMenu();
		this.$addTagInput.val('');
		this.$addTagInput.focus();
	},

	killSearchMenu: function()
	{
		this.searchMenu.hide();
		this.searchMenu.destroy();
		this.searchMenu = null;
	},

	_attachHUDEvents: function ()
	{
		this.removeListener(this.$elements, 'dlbclick');
		this.addListener(this.$elements, 'dblclick', $.proxy(this, '_editProperties'));
	},

	_editProperties: function (event)
	{
		var $target = $(event.currentTarget);
		if (!$target.data('ElementEditor'))
		{
			var settings = {
				elementId: $target.attr('data-id'),
				$trigger: $target,
				loadContentAction: 'tags/editTagContent',
				saveContentAction: 'tags/saveTagContent'
			};
			$target.data('ElementEditor', new Craft.ElementEditor(settings));
		}

		$target.data('ElementEditor').show();
	}

});
