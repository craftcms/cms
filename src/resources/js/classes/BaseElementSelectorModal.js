/**
 * Element selector modal class
 */
Craft.BaseElementSelectorModal = Garnish.Modal.extend({

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

	init: function(elementType, settings)
	{
		this.elementType = elementType;
		this.setSettings(settings, Craft.BaseElementSelectorModal.defaults);

		// Build the modal
		var $container = $('<div class="modal elementselectormodal"></div>').appendTo(Garnish.$bod),
			$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo($container),
			$footer = $('<div class="footer"/>').appendTo($container),
			$buttons = $('<div class="buttons rightalign"/>').appendTo($footer),
			$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons),
			$selectBtn = $('<div class="btn disabled submit">'+Craft.t('Select')+'</div>').appendTo($buttons);

		this.base($container, settings);

		this.$body = $body;
		this.$selectBtn = $selectBtn;

		this.addListener($cancelBtn, 'activate', 'cancel');
		this.addListener(this.$selectBtn, 'activate', 'selectElements');
	},

	onFadeIn: function()
	{
		if (!this.elementIndex)
		{
			// Get the modal body HTML based on the settings
			var data = {
				mode:        'modal',
				elementType: this.elementType,
				sources:     this.settings.sources
			};

			Craft.postActionRequest('elements/getModalBody', data, $.proxy(function(response, textStatus) {

				if (textStatus == 'success')
				{
					this.$body.html(response);

					// Initialize the element index
					this.elementIndex = Craft.createElementIndex(this.elementType, this.$body, {
						mode:               'modal',
						id:                 this.settings.id,
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

		var $trs = this.elementIndex.$elementContainer.children(':not(.disabled)');

		this.elementSelect = new Garnish.Select(this.elementIndex.$elementContainer, $trs, {
			multi: this.settings.multiSelect,
			vertical: (this.elementIndex.getState('view') == 'table'),
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
		this.settings.onCancel();
	},

	selectElements: function()
	{
		if (this.elementIndex && this.elementSelect && this.elementSelect.totalSelected)
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
				this.elementIndex.disableElements($selectedRows);
			}
		}
	}
},
{
	defaults: {
		id: null,
		sources: null,
		criteria: null,
		multiSelect: false,
		disabledElementIds: [],
		disableOnSelect: false,
		onCancel: $.noop,
		onSelect: $.noop
	}
});
