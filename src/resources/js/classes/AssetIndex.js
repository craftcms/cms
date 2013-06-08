/**
 * Asset index class
 */
Craft.AssetIndex = Craft.BaseElementIndex.extend({

	init: function(elementType, $container, settings)
	{
		console.log('Asset index!');
		this.base(elementType, $container, settings);
	}

});

// Register it!
Craft.registerElementIndexClass('Asset', Craft.AssetIndex);
