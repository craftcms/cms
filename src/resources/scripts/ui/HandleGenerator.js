(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Handle Generator
 */
blx.ui.HandleGenerator = blx.Base.extend({

	$nameField: null,
	$handleField: null,

	init: function(nameField, handleField)
	{
		this.$nameField = $(nameField);
		this.$handleField = $(handleField);

		var events = 'keypress,keyup,change,change,blur';

		this.addListener(this.$nameField, events, 'updateHandle');
		this.addListener(this.$handleField, events, 'stopUpdatingHandle');
	},

	updateHandle: function()
	{
		var handle = this.$nameField.val();

		// Make it lowercase
		handle = handle.toLowerCase();

		// Remove HTML tags
		handle = handle.replace("/<(.*?)>/g", '');

		// Convert extended ASCII characters to basic ASCII
		handle = blx.utils.asciiString(handle);

		// Hyphenate
		handle = handle.replace(/(\s|\/|\\|\+\-)+/, '-');

		// Strip out non alphanumeric/hyphen/underscore/period characters
		handle = handle.replace(/[^a-z0-9\-\._]/g, '');

		// Handle must start with a letter and end with a letter/number
		handle = handle.replace(/^[^a-z]+/, '');
		handle = handle.replace(/[^a-z0-9]+$/, '');

		this.$handleField.val(handle);
	},

	stopUpdatingHandle: function()
	{
		this.removeAllListeners(this.$nameField);
		this.removeAllListeners(this.$handleField);
	}
});


})(jQuery);
