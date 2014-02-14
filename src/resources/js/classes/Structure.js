/**
 * Structure class
 */
Craft.Structure = Garnish.Base.extend({

	$container: null,
	state: null,
	structureDrag: null,

	/**
	 * Init
	 */
	init: function(container, settings)
	{
		this.$container = $(container);
		this.setSettings(settings, Craft.Structure.defaults);

		this.state = {};

		if (this.settings.storageKey)
		{
			$.extend(this.state, Craft.getLocalStorage(this.settings.storageKey, {}));
		}

		if (typeof this.state.collapsedElementIds == 'undefined')
		{
			this.state.collapsedElementIds = [];
		}

		var $parents = this.$container.find('ul').prev('.row');

		for (var i = 0; i < $parents.length; i++)
		{
			var $row = $($parents[i]),
				$li = $row.parent(),
				$toggle = $('<div class="toggle" title="'+Craft.t('Show/hide children')+'"/>').prependTo($row);

			if ($.inArray($row.children('.element').data('id'), this.state.collapsedElementIds) != -1)
			{
				$li.addClass('collapsed');
			}

			this.initToggle($toggle);
		}

		if (this.settings.sortable)
		{
			this.$container.find('.add').click($.proxy(function(ev)
			{
				var $btn = $(ev.currentTarget);

				if (!$btn.data('menubtn'))
				{
					var elementId = $btn.parent().children('.element').data('id'),
						newChildUrl = Craft.getUrl(this.settings.newChildUrl, 'parentId='+elementId),
						$menu = $('<div class="menu"><ul><li><a href="'+newChildUrl+'">'+Craft.t('New child')+'</a></li></ul></div>').insertAfter($btn);

					var menuBtn = new Garnish.MenuBtn($btn);
					menuBtn.showMenu();
				}

			}, this));

			this.structureDrag = new Craft.StructureDrag(this, this.settings.moveAction, this.settings.maxLevels);
		}
	},

	initToggle: function($toggle)
	{
		$toggle.click($.proxy(function(ev) {

			var $li = $(ev.currentTarget).closest('li'),
				elementId = $li.children('.row').children('.element').data('id'),
				viewStateKey = $.inArray(elementId, this.state.collapsedElementIds);

			if ($li.hasClass('collapsed'))
			{
				$li.removeClass('collapsed');

				if (viewStateKey != -1)
				{
					this.state.collapsedElementIds.splice(viewStateKey, 1);
				}
			}
			else
			{
				$li.addClass('collapsed');

				if (viewStateKey == -1)
				{
					this.state.collapsedElementIds.push(elementId);
				}
			}

			if (this.settings.storageKey)
			{
				Craft.setLocalStorage(this.settings.storageKey, this.state);
			}

		}, this));
	}
},
{
	defaults: {
		storageKey:  null,
		sortable:    false,
		newChildUrl: null,
		moveAction:  null,
		maxLevels:   null
	}
});
