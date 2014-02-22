/**
 * Element selector modal class
 */
Craft.BaseElementSelectorModal = Garnish.Modal.extend(
{
	elementType: null,
	elementIndex: null,
	elementSelect: null,

	$body: null,
	$selectBtn: null,
	$sidebar: null,
	$sources: null,
	$sourceToggles: null,
	$main: null,
	$search: null,
	$elements: null,
	$tbody: null,
	$buttons: null,
	$cancelBtn: null,
	$selectBtn: null,

	init: function(elementType, settings)
	{
		this.elementType = elementType;
		this.setSettings(settings, Craft.BaseElementSelectorModal.defaults);

		// Build the modal
		var $container = $('<div class="modal elementselectormodal"></div>').appendTo(Garnish.$bod),
			$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo($container),
			$footer = $('<div class="footer"/>').appendTo($container);

		this.base($container, settings);

		this.$buttons = $('<div class="buttons rightalign"/>').appendTo($footer);
		this.$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo(this.$buttons);
		this.$selectBtn = $('<div class="btn disabled submit">'+Craft.t('Select')+'</div>').appendTo(this.$buttons);

		this.$body = $body;

		this.addListener(this.$cancelBtn, 'activate', 'cancel');
		this.addListener(this.$selectBtn, 'activate', 'selectElements');
	},

	onFadeIn: function()
	{
		if (!this.elementIndex)
		{
			// Get the modal body HTML based on the settings
			var data = {
				context:     'modal',
				elementType: this.elementType,
				sources:     this.settings.sources
			};

			Craft.postActionRequest('elements/getModalBody', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					this.$body.html(response);

					if (this.$body.has('.sidebar:not(.hidden)').length)
					{
						this.$body.addClass('has-sidebar');
					}

					// Initialize the element index
					this.elementIndex = Craft.createElementIndex(this.elementType, this.$body, {
						context:            'modal',
						storageKey:         this.settings.storageKey,
						criteria:           this.settings.criteria,
						disabledElementIds: this.settings.disabledElementIds,
						onUpdateElements:   $.proxy(this, 'onUpdateElements'),
						onEnableElements:   $.proxy(this, 'onEnableElements'),
						onDisableElements:  $.proxy(this, 'onDisableElements')
					});
				}

			}, this));
		}
		else
		{
			// Auto-focus the Search box
			if (!Garnish.isMobileBrowser(true))
			{
				this.elementIndex.$search.focus();
			}
		}

		this.base();
	},

	onUpdateElements: function(appended)
	{
		if (!appended)
		{
			this.addListener(this.elementIndex.$elementContainer, 'dblclick', 'selectElements');
		}

		// Reset the element select
		if (this.elementSelect)
		{
			this.elementSelect.destroy();
			delete this.elementSelect;
		}

		if (this.elementIndex.getSelectedSourceState('mode') == 'structure')
		{
			var $items = this.elementIndex.$elementContainer.find('.row:not(.disabled)');
		}
		else
		{
			var $items = this.elementIndex.$elementContainer.children(':not(.disabled)');
		}

		this.elementSelect = new Garnish.Select(this.elementIndex.$elementContainer, $items, {
			multi: this.settings.multiSelect,
			vertical: (this.elementIndex.getSelectedSourceState('mode') != 'thumbs'),
			onSelectionChange: $.proxy(this, 'onSelectionChange')
		});

        this.elementIndex.setElementSelect(this.elementSelect);
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

	onEnableElements: function($elements)
	{
		this.elementSelect.addItems($elements);
	},

	onDisableElements: function($elements)
	{
		this.elementSelect.removeItems($elements);
	},

	cancel: function()
	{
		this.hide();
	},

	selectElements: function()
	{
		if (this.elementIndex && this.elementSelect && this.elementSelect.totalSelected)
		{
			this.elementSelect.clearMouseUpTimeout();
			this.hide();

			var $selectedItems = this.elementSelect.getSelectedItems(),
				elementInfo = this.getElementInfo($selectedItems);

			this.onSelect(elementInfo);

			if (this.settings.disableOnSelect)
			{
				this.elementIndex.disableElements(this.elementSelect.getSelectedItems());
			}
		}
	},

	getElementInfo: function($selectedItems)
	{
		var info = [];

		for (var i = 0; i < $selectedItems.length; i++)
		{
			var $item = $($selectedItems[i]);

			info.push(Craft.getElementInfo($item));
		}

		return info;
	},

	onSelect: function(elementInfo)
	{
		this.settings.onSelect(elementInfo);
	}
},
{
	defaults: {
		storageKey: null,
		sources: null,
		criteria: null,
		multiSelect: false,
		disabledElementIds: [],
		disableOnSelect: false,
		onCancel: $.noop,
		onSelect: $.noop
	}
});
