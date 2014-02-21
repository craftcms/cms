/**
 * Handle Generator
 */
Craft.EntryUrlFormatGenerator = Craft.BaseInputGenerator.extend(
{
	generateTargetValue: function(sourceVal)
	{
		// Remove HTML tags
		sourceVal = sourceVal.replace("/<(.*?)>/g", '');

		// Make it lowercase
		sourceVal = sourceVal.toLowerCase();

		// Convert extended ASCII characters to basic ASCII
		sourceVal = Craft.asciiString(sourceVal);

		// Handle must start with a letter and end with a letter/number
		sourceVal = sourceVal.replace(/^[^a-z]+/, '');
		sourceVal = sourceVal.replace(/[^a-z0-9]+$/, '');

		// Get the "words"
		var words = Craft.filterArray(sourceVal.split(/[^a-z0-9]+/));

		var urlFormat = words.join('-');

		if (urlFormat && this.settings.suffix)
		{
			urlFormat += this.settings.suffix;
		}

		return urlFormat;
	}
});
