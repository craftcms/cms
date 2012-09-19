(function($) {

/**
 * Handle Generator
 */
Blocks.ui.EntryUrlFormatGenerator = Blocks.ui.InputGenerator.extend({

	generateTargetValue: function(sourceVal)
	{
		// Remove HTML tags
		sourceVal = sourceVal.replace("/<(.*?)>/g", '');

		// Make it lowercase
		sourceVal = sourceVal.toLowerCase();

		// Convert extended ASCII characters to basic ASCII
		sourceVal = Blocks.asciiString(sourceVal);

		// Handle must start with a letter and end with a letter/number
		sourceVal = sourceVal.replace(/^[^a-z]+/, '');
		sourceVal = sourceVal.replace(/[^a-z0-9]+$/, '');

		// Get the "words"
		var words = Blocks.filterArray(sourceVal.split(/[^a-z0-9]+/));

		if (words.length)
			return words.join('-') + '/{slug}';

		return '';
	}
});

})(jQuery);
