/**
 * Asset selector modal class
 */
Craft.AssetSelectorModal = Craft.BaseElementSelectorModal.extend({

	init: function(elementType, settings)
	{
		console.log('Asset selector modal!');
		this.base(elementType, settings);
	}

});

// Register it!
Craft.registerElementSelectorModalClass('Asset', Craft.AssetSelectorModal);
