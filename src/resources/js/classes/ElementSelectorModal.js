/**
 * Element selector modal class
 */
Craft.ElementSelectorModal = Garnish.Modal.extend({

	initialized: false,
	source: null,
	searchTimeout: null,
	elementSelect: null,

	$body: null,
	$selectBtn: null,
	$spinner: null,
	$sources: null,
	$search: null,
	$elements: null,

	init: function(settings)
	{
		this.setSettings(settings, Craft.ElementSelectorModal.defaults);

		// Build the modal
		var $container = $('<div class="modal elementselector"></div>').appendTo(Garnish.$bod),
			$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo($container),
			$footer = $('<div class="footer"/>').appendTo($container),
			$buttons = $('<div class="buttons rightalign"/>').appendTo($footer),
			$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons),
			$selectBtn = $('<div class="btn disabled submit">'+Craft.t('Select')+'</div>').appendTo($buttons);

		this.base($container);

		this.$body = $body;
		this.$selectBtn = $selectBtn;

		this.addListener($cancelBtn, 'activate', 'cancel');
		this.addListener(this.$selectBtn, 'activate', 'selectElements');
	},

	onFadeIn: function()
	{
		if (!this.initialized)
		{
			// Get the modal body HTML based on the settings
			var data = {
				elementType:        this.settings.elementType,
				sources:            this.settings.sources,
				disabledElementIds: this.settings.disabledElementIds
			};

			Craft.postActionRequest('elements/getModalBody', data, $.proxy(function(response)
			{
				// Initialize the contents
				this.$body.html(response);

				this.$spinner = this.$body.find('.spinner:first');
				this.$sources = this.$body.find('.sidebar:first a');
				this.$search = this.$body.find('.search:first input:first');
				this.$elements = this.$body.find('.elements:first');

				this.$source = this.$sources.filter('.sel');
				this.source = this.$source.data('id');

				this.resetElementSelect();

				this.addListener(this.$search, 'textchange', $.proxy(function()
				{
					if (this.searchTimeout)
					{
						clearTimeout(this.searchTimeout);
					}

					this.searchTimeout = setTimeout($.proxy(this, 'updateElements'), 500);
				}, this));

				this.addListener(this.$sources, 'activate', 'selectSource');
				this.addListener(this.$elements, 'dblclick', 'selectElements');
			}, this));

			this.initialized = true;
		}

		this.base();
	},

	updateElements: function()
	{
		this.$spinner.removeClass('hidden');

		var data = {
			elementType: this.settings.elementType,
			source:      this.source,
			search:      this.$search.val(),
			filter:      ':not(a)'
		};

		Craft.postActionRequest('elements/getElements', data, $.proxy(function(response)
		{
			this.$elements.html(response);
			this.$spinner.addClass('hidden');
			this.resetElementSelect();
		}, this));
	},

	resetElementSelect: function()
	{
		if (this.elementSelect)
		{
			this.elementSelect.destroy();
			delete this.elementSelect;
		}

		var $trs = this.$elements.find('tbody:first > tr');

		this.elementSelect = new Garnish.Select(this.$elements, $trs, {
			multi: true,
			vertical: true,
			waitForDblClick: true,
			onSelectionChange: $.proxy(this, 'onSelectionChange')
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

	cancel: function()
	{
		this.hide();
		this.settings.onCancel();
	},

	selectSource: function(ev)
	{
		this.$source.removeClass('sel');
		this.$source = $(ev.currentTarget).addClass('sel');
		this.source = this.$source.data('id');
		this.updateElements();
	},

	selectElements: function()
	{
		if (this.elementSelect && this.elementSelect.totalSelected)
		{
			var $selectedRows = this.elementSelect.getSelectedItems(),
				elements = [];

			for (var i = 0; i < $selectedRows.length; i++)
			{
				var $row = $($selectedRows[i]);

				elements.push({
					id: $row.data('id'),
					label: $row.data('label')
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

	enableElements: function($elements)
	{
		$elements.removeClass('disabled');
		this.elementSelect.addItems($elements);
	},

	disableElements: function($elements)
	{
		$elements.removeClass('sel').addClass('disabled');
		this.elementSelect.removeItems($elements);
	},

	getElementsById: function(elementIds)
	{
		elementIds = $.makeArray(elementIds);
		var $elements = $();

		for (var i = 0; i < elementIds.length; i++)
		{
			$elements = $elements.add(this.$elements.find('tbody:first > tr[data-id='+elementIds[i]+']'));
		}

		return $elements;
	},

	enableElementsById: function(elementIds)
	{
		var $elements = this.getElementsById(elementIds);
		this.enableElements($elements);
	},

	disableElementsById: function(elementIds)
	{
		var $elements = this.getElementsById(elementIds);
		this.disableElements($elements);
	}
},
{
	defaults: {
		elementType: null,
		sources: null,
		disabledElementIds: null,
		disableOnSelect: true,
		onCancel: $.noop,
		onSelect: $.noop
	}
});
