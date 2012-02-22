(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Handle Generator
 */
blx.ui.HandleGenerator = blx.Base.extend({

	$nameField: null,
	$handleField: null,

	init: function(nameField, handleField, settings)
	{
		this.$nameField = $(nameField);
		this.$handleField = $(handleField);

		var events = 'keypress,keyup,change,change,blur';

		this.addListener(this.$nameField, events, 'updateHandle');
		this.addListener(this.$handleField, events, 'stopUpdatingHandle');
	},

	updateHandle: function()
	{
		var nameVal = this.$nameField.val();

		// Remove HTML tags
		nameVal = nameVal.replace("/<(.*?)>/g", '');

		// Make it lowercase
		nameVal = nameVal.toLowerCase();

		// Convert extended ASCII characters to basic ASCII
		nameVal = blx.utils.asciiString(nameVal);

		// Handle must start with a letter and end with a letter/number
		nameVal = nameVal.replace(/^[^a-z]+/, '');
		nameVal = nameVal.replace(/[^a-z0-9]+$/, '');

		// Get the "words"
		var words = blx.utils.filterArray(nameVal.split(/[^a-z0-9]+/));

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

		this.$handleField.val(handle);
	},

	stopUpdatingHandle: function()
	{
		this.removeAllListeners(this.$nameField);
		this.removeAllListeners(this.$handleField);
	}
});


})(jQuery);
