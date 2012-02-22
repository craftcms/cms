(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Handle Generator
 */
blx.ui.EntryUrlFormatGenerator = blx.ui.InputGenerator.extend({

	generateTargetValue: function(sourceVal)
	{
		// Remove HTML tags
		sourceVal = sourceVal.replace("/<(.*?)>/g", '');

		// Make it lowercase
		sourceVal = sourceVal.toLowerCase();

		// Convert extended ASCII characters to basic ASCII
		sourceVal = blx.utils.asciiString(sourceVal);

		// Handle must start with a letter and end with a letter/number
		sourceVal = sourceVal.replace(/^[^a-z]+/, '');
		sourceVal = sourceVal.replace(/[^a-z0-9]+$/, '');

		// Get the "words"
		var words = blx.utils.filterArray(sourceVal.split(/[^a-z0-9]+/));

		return words.join('-') + '/{slug}';
	}
});


})(jQuery);
