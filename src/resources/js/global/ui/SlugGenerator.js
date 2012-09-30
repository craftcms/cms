(function($) {

/**
 * Slug Generator
 */
Blocks.ui.SlugGenerator = Blocks.ui.InputGenerator.extend({

	generateTargetValue: function(sourceVal)
	{
		// Remove HTML tags
		sourceVal = sourceVal.replace("/<(.*?)>/g", '');

		// Make it lowercase
		sourceVal = sourceVal.toLowerCase();

		// Convert extended ASCII characters to basic ASCII
		sourceVal = Blocks.asciiString(sourceVal);

		// Slug must start and end with alphanumeric characters
		sourceVal = sourceVal.replace(/^[^a-z0-9]+/, '');
		sourceVal = sourceVal.replace(/[^a-z0-9]+$/, '');

		// Get the "words"
		var words = Blocks.filterArray(sourceVal.split(/[^a-z0-9]+/));

		if (words.length)
		{
			return words.join('-');
		}
		else
		{
			return '';
		}
	}
});

})(jQuery);
