/**
 * Element selector modal class
 */
Craft.ElementSelectorModal = Garnish.Modal.extend({

	initialized: false,
	state: null,
	stateStorageId: null,
	source: null,
	searchTimeout: null,
	elementSelect: null,

	$body: null,
	$selectBtn: null,
	$spinner: null,
	$sources: null,
	$main: null,
	$search: null,
	$elements: null,
	$headers: null,
	$tbody: null,

	init: function(settings)
	{
		this.setSettings(settings, Craft.ElementSelectorModal.defaults);

		// Build the modal
		var $container = $('<div class="modal elementselectormodal"></div>').appendTo(Garnish.$bod),
			$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo($container),
			$footer = $('<div class="footer"/>').appendTo($container),
			$buttons = $('<div class="buttons rightalign"/>').appendTo($footer),
			$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons),
			$selectBtn = $('<div class="btn disabled submit">'+Craft.t('Select')+'</div>').appendTo($buttons);

		this.base($container);

		this.$body = $body;
		this.$selectBtn = $selectBtn;

        // Set the state object
        this.state = {};

        if (typeof Storage !== 'undefined' && this.settings.id)
        {
        	this.stateStorageId = 'Craft.ElementSelectorModal.'+this.settings.id;

        	if (typeof localStorage[this.stateStorageId] != 'undefined')
        	{
        		$.extend(this.state, JSON.parse(localStorage[this.stateStorageId]));
        	}
        }

		this.addListener($cancelBtn, 'activate', 'cancel');
		this.addListener(this.$selectBtn, 'activate', 'selectElements');
	},

	getState: function(key)
	{
		if (typeof this.state[key] != 'undefined')
		{
			return this.state[key];
		}
		else
		{
			return null;
		}
	},

	setState: function(key, value)
	{
		if (typeof key == 'object')
		{
			$.extend(this.state, key);
		}
		else
		{
			this.state[key] = value;
		}

		if (this.stateStorageId)
		{
		    localStorage[this.stateStorageId] = JSON.stringify(this.state);
		}
	},

	getControllerData: function()
	{
		return {
			elementType:        this.settings.elementType,
			disabledElementIds: this.settings.disabledElementIds,
			state:              this.state,
			search:             (this.$search ? this.$search.val() : null)
		};
	},

	onFadeIn: function()
	{
		if (!this.initialized)
		{
			// Get the modal body HTML based on the settings
			var data = this.getControllerData();
			data.sources = this.settings.sources;

			Craft.postActionRequest('elements/getModalBody', data, $.proxy(function(response)
			{
				// Initialize the contents
				this.$body.html(response.bodyHtml);

				this.$spinner = this.$body.find('.spinner:first');
				this.$sources = this.$body.find('.sidebar:first a');
				this.$main = this.$body.find('.main:first');
				this.$search = this.$main.find('.search:first input:first');
				this.$elements = this.$main.find('.elements:first');

				this.$source = this.$sources.filter('.sel');

				this.addListener(this.$sources, 'activate', 'selectSource');
				this.addListener(this.$search, 'textchange', $.proxy(function()
				{
					if (this.searchTimeout)
					{
						clearTimeout(this.searchTimeout);
					}

					this.searchTimeout = setTimeout($.proxy(this, 'updateElements'), 500);
				}, this));

				this.setNewElementContainerHtml(response);

			}, this));

			this.initialized = true;
		}

		this.base();
	},

	setNewElementContainerHtml: function(response)
	{
		this.$elements.html(response.elementContainerHtml);

		var $headers = this.$main.find('thead:first > th');
		this.addListener($headers, 'activate', 'onSortChange');

		this.$tbody = this.$elements.find('tbody:first');

		this.addListener(this.$tbody, 'dblclick', 'selectElements');

		this.setNewElementDataHtml(response);
	},

	setNewElementDataHtml: function(response, append)
	{
		if (append)
		{
			this.$tbody.append(response.elementDataHtml);
		}
		else
		{
			this.$tbody.html(response.elementDataHtml);
		}

		$('head').append(response.headHtml);

		// Reset the element select
		if (this.elementSelect)
		{
			this.elementSelect.destroy();
			delete this.elementSelect;
		}

		var $trs = this.$tbody.children(':not(.disabled)');

		this.elementSelect = new Garnish.Select(this.$tbody, $trs, {
			multi: true,
			vertical: true,
			waitForDblClick: true,
			onSelectionChange: $.proxy(this, 'onSelectionChange')
		});

		if (response.more)
		{
			this.totalVisible = response.totalVisible;
			this.prepareToLoadMore();
		}
	},

	updateElements: function()
	{
		this.$spinner.removeClass('hidden');
		this.removeListener(this.$main, 'scroll');

		var data = this.getControllerData();

		Craft.postActionRequest('elements/getModalElements', data, $.proxy(function(response)
		{
			this.$spinner.addClass('hidden');

			this.setNewElementContainerHtml(response);
		}, this));
	},

	prepareToLoadMore: function()
	{
		this.addListener(this.$main, 'scroll', function()
		{
			if (this.$main.prop('scrollHeight') - this.$main.scrollTop() == this.$main.outerHeight())
			{
				this.$spinner.removeClass('hidden');
				this.removeListener(this.$main, 'scroll');

				var data = this.getControllerData();
				data.offset = this.totalVisible;

				Craft.postActionRequest('elements/getModalElements', data, $.proxy(function(response)
				{
					this.$spinner.addClass('hidden');

					this.setNewElementDataHtml(response, true);
				}, this));
			}
		});
	},

	onSelectionChange: function()
	{
		if (this.elementSelect.totalSelected)
		{
			this.$selectBtn.removeClass('disabled');
		}
		else
		{
			this.$selectBtn.addClass('disabled');
		}
	},

	onSortChange: function(ev)
	{
		var $th = $(ev.currentTarget),
			attribute = $th.attr('data-attribute');

		if (this.getState('order') == attribute)
		{
			if (this.getState('sort') == 'asc')
			{
				this.setState('sort', 'desc');
			}
			else
			{
				this.setState('sort', 'asc');
			}
		}
		else
		{
			this.setState({
				order: attribute,
				sort: 'asc'
			});
		}

		this.updateElements();
	},

	cancel: function()
	{
		this.hide();
		this.settings.onCancel();
	},

	selectSource: function(ev)
	{
		this.$source.removeClass('sel');
		this.$source = $(ev.currentTarget).addClass('sel');

		this.setState('source', this.$source.data('id'));
		this.updateElements();
	},

	selectElements: function()
	{
		if (this.elementSelect && this.elementSelect.totalSelected)
		{
			this.elementSelect.clearMouseUpTimeout();

			var $selectedRows = this.elementSelect.getSelectedItems(),
				elements = [];

			for (var i = 0; i < $selectedRows.length; i++)
			{
				var $row = $($selectedRows[i]),
					$element = $row.find('.element');

				elements.push({
					id: $row.data('id'),
					label: $row.data('label'),
					status: $row.data('status'),
					hasThumb: $element.hasClass('hasthumb'),
					$element: $element
				});
			}

			this.hide();
			this.settings.onSelect(elements);

			if (this.settings.disableOnSelect)
			{
				this.disableElements($selectedRows);
			}
		}
	},

	rememberDisabledElementId: function(elementId)
	{
		var index = $.inArray(elementId, this.settings.disabledElementIds);

		if (index == -1)
		{
			this.settings.disabledElementIds.push(elementId);
		}
	},

	forgetDisabledElementId: function(elementId)
	{
		var index = $.inArray(elementId, this.settings.disabledElementIds);

		if (index != -1)
		{
			this.settings.disabledElementIds.splice(index, 1);
		}
	},

	enableElements: function($elements)
	{
		$elements.removeClass('disabled');
		this.elementSelect.addItems($elements);

		for (var i = 0; i < $elements.length; i++)
		{
			var elementId = $($elements[i]).data('id');
			this.forgetDisabledElementId(elementId);
		}
	},

	disableElements: function($elements)
	{
		$elements.removeClass('sel').addClass('disabled');
		this.elementSelect.removeItems($elements);

		for (var i = 0; i < $elements.length; i++)
		{
			var elementId = $($elements[i]).data('id');
			this.rememberDisabledElementId(elementId);
		}
	},

	getElementById: function(elementId)
	{
		return this.$tbody.children('[data-id='+elementId+']:first');
	},

	enableElementsById: function(elementIds)
	{
		elementIds = $.makeArray(elementIds);

		for (var i = 0; i < elementIds.length; i++)
		{
			var elementId = elementIds[i],
				$element = this.getElementById(elementId);

			if ($element.length)
			{
				this.enableElements($element);
			}
			else
			{
				this.forgetDisabledElementId(elementId);
			}
		}
	},

	disableElementsById: function(elementIds)
	{
		elementIds = $.makeArray(elementIds);

		for (var i = 0; i < elementIds.length; i++)
		{
			var elementId = elementIds[i],
				$element = this.getElementById(elementId);

			if ($element.length)
			{
				this.disableElements($element);
			}
			else
			{
				this.rememberDisabledElementId(elementId);
			}
		}
	}
},
{
	defaults: {
		id: null,
		elementType: null,
		sources: null,
		disabledElementIds: null,
		disableOnSelect: true,
		onCancel: $.noop,
		onSelect: $.noop
	}
});
