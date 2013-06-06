/**
 * Asset selector modal class
 */
Craft.AssetSelectorModal = Craft.BaseElementSelectorModal.extend({

	init: function(elementType, settings)
	{
		console.log('Hey there!');
		this.base(elementType, settings);
	}

});

// Register it!
Craft.registerElementSelectorModal('Asset', Craft.AssetSelectorModal);
