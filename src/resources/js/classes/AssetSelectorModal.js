/**
 * Asset selector modal class
 */
Craft.AssetSelectorModal = Craft.BaseElementSelectorModal.extend(
{
	$selectTransformBtn: null,
	$transformSpinner: null,
	_selectedTransform: null,

	init: function(elementType, settings)
	{
		settings = $.extend({}, Craft.AssetSelectorModal.defaults, settings);

		if (settings.canSelectImageTransforms)
		{
			if (typeof Craft.AssetSelectorModal.transforms == 'undefined')
			{
				var base = this.base;

				this.fetchTransformInfo($.proxy(function()
				{
					// Finally call this.base()
					base.call(this, elementType, settings);

					this.createSelectTransformButton();
				}, this));

				// Prevent this.base() from getting called until later
				return;
			}
		}

		this.base(elementType, settings);

		if (settings.canSelectImageTransforms)
		{
			this.createSelectTransformButton();
		}
	},

	fetchTransformInfo: function(callback)
	{
		Craft.postActionRequest('assets/getTransformInfo', $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success' && response instanceof Array)
			{
				Craft.AssetSelectorModal.transforms = response;
			}
			else
			{
				Craft.AssetSelectorModal.transforms = [];
			}

			callback();

		}, this));
	},

	createSelectTransformButton: function()
	{
		if (!Craft.AssetSelectorModal.transforms.length)
		{
			return;
		}

		var $btnGroup = $('<div class="btngroup"/>').appendTo(this.$buttons);
		this.$selectBtn.appendTo($btnGroup);

		this.$selectTransformBtn = $('<div class="btn menubtn disabled">'+Craft.t('Select Transform')+'</div>').appendTo($btnGroup);

		var $menu = $('<div class="menu" data-align="right"></div>').insertAfter(this.$selectTransformBtn),
			$menuList = $('<ul></ul>').appendTo($menu);

		for (var i = 0; i < Craft.AssetSelectorModal.transforms.length; i++)
		{
			$('<li><a data-transform="'+Craft.AssetSelectorModal.transforms[i].handle+'">'+Craft.AssetSelectorModal.transforms[i].name+'</a></li>').appendTo($menuList);
		}

		new Garnish.MenuBtn(this.$selectTransformBtn, {
			onOptionSelect: $.proxy(this, 'onSelectTransform')
		});

		this.$transformSpinner = $('<div class="spinner hidden" style="margin-right: -24px;"/>').insertAfter($btnGroup);
	},

	onSelectionChange: function(ev)
	{
		if (this.elementSelect.totalSelected && this.settings.canSelectImageTransforms && Craft.AssetSelectorModal.transforms.length)
		{
			var allowTransforms = true,
				$selectedItems = this.elementSelect.getSelectedItems();

			for (var i = 0; i < $selectedItems.length; i++)
			{
				if (!$('.element.hasthumb:first', $selectedItems[i]).length)
				{
					allowTransforms = false;
					break;
				}
			}
		}
		else
		{
			var allowTransforms = false;
		}

		if (allowTransforms)
		{
			this.$selectTransformBtn.removeClass('disabled');
		}
		else if (this.$selectTransformBtn)
		{
			this.$selectTransformBtn.addClass('disabled');
		}

		this.base();
	},

	onSelectTransform: function(option)
	{
		var transform = $(option).data('transform');
		this.selectImagesWithTransform(transform);
	},

	selectImagesWithTransform: function(transform)
	{
		// First we must get any missing transform URLs
		if (typeof Craft.AssetSelectorModal.transformUrls[transform] == 'undefined')
		{
			Craft.AssetSelectorModal.transformUrls[transform] = {};
		}

		var $selectedItems = this.elementSelect.getSelectedItems(),
			imageIdsWithMissingUrls = [];

		for (var i = 0; i < $selectedItems.length; i++)
		{
			var $item = $($selectedItems[i]),
				elementId = $item.data('id');

			if (typeof Craft.AssetSelectorModal.transformUrls[transform][elementId] == 'undefined')
			{
				imageIdsWithMissingUrls.push(elementId);
			}
		}

		if (imageIdsWithMissingUrls.length)
		{
			this.$transformSpinner.removeClass('hidden');
			this.fetchMissingTransformUrls(imageIdsWithMissingUrls, transform, $.proxy(function()
			{
				this.$transformSpinner.addClass('hidden');
				this.selectImagesWithTransform(transform);
			}, this));
		}
		else
		{
			this._selectedTransform = transform;
			this.selectElements();
			this._selectedTransform = null;
		}
	},

	fetchMissingTransformUrls: function(imageIdsWithMissingUrls, transform, callback)
	{
		var elementId = imageIdsWithMissingUrls.pop();

		var data = {
			fileId: elementId,
			handle: transform
		};

		Craft.postActionRequest('assets/generateTransform', data, $.proxy(function(response, textStatus)
		{
			Craft.AssetSelectorModal.transformUrls[transform][elementId] = false;

			if (textStatus == 'success')
			{
				var parts = response.split(':');

				if (parts[0] == 'success')
				{
					Craft.AssetSelectorModal.transformUrls[transform][elementId] = response.replace(/^success:/, '');
				}
			}

			// More to load?
			if (imageIdsWithMissingUrls.length)
			{
				this.fetchMissingTransformUrls(imageIdsWithMissingUrls, transform, callback);
			}
			else
			{
				callback();
			}
		}, this));
	},

	getElementInfo: function($selectedItems)
	{
		var info = this.base($selectedItems);

		if (this._selectedTransform)
		{
			for (var i = 0; i < info.length; i++)
			{
				var elementId = info[i].id;

				if (
					typeof Craft.AssetSelectorModal.transformUrls[this._selectedTransform][elementId] != 'undefined' &&
					Craft.AssetSelectorModal.transformUrls[this._selectedTransform][elementId] !== false
				)
				{
					info[i].url = Craft.AssetSelectorModal.transformUrls[this._selectedTransform][elementId];
				}
			}
		}

		return info;
	},

	onSelect: function(elementInfo)
	{
		this.settings.onSelect(elementInfo, this._selectedTransform);
	}
},
{
	defaults: {
		canSelectImageTransforms: false
	},

	transformUrls: {}
});

// Register it!
Craft.registerElementSelectorModalClass('Asset', Craft.AssetSelectorModal);
