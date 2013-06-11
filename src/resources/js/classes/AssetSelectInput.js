/**
 * Element Select input
 */
Craft.AssetSelectInput = Craft.BaseElementSelectInput.extend({

	init: function(id, name, elementType, sources, limit)
	{
		console.log('Asset select input!');
		this.base(id, name, elementType, sources, limit);
	}
});
