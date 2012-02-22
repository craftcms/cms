(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Handle Generator
 */
blx.ui.HandleGenerator = blx.ui.InputGenerator.extend({

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

		if (words)
		{
			var handle = words[0];

			for (var i = 1; i < words.length; i++)
			{
				handle += blx.utils.uppercaseFirst(words[i]);
			}
		}
		else
		{
			var handle = '';
		}

		return handle;
	}
});


})(jQuery);
