(function($) {


var autoUpdateSlug = true;


var UrlInput = blx.ui.TitleInput.extend({

	$div: null,
	$prefix: null,

	init: function()
	{
		this.$container = $('.page-head .url .editable');
		this.$div = this.$container.find('div');
		this.$prefix = this.$div.find('.prefix');
		this.$heading = this.$div.find('.slug');
		this.$hiddenInput = this.$container.find('input');

		this.settings = $.extend({}, blx.ui.TitleInput.defaults);
		this.settings.untitledText = '…';

		this.val = this.$hiddenInput.val();

		this.addListener(this.$container, 'focus,click', 'showInput');
	},

	createInput: function()
	{
		this.base();
		this.$input.removeClass('title-input').addClass('url-input');
		autoUpdateSlug = false;
	},

	showInput: function()
	{
		this.base();
		this.$prefix.insertBefore(this.$input);
	},

	hideInput: function()
	{
		this.base();
		this.$prefix.prependTo(this.$div);
	}

});

var urlInput = new UrlInput(),
	$sidebarLabel,
	$slug = $('.page-head .url .slug'),
	$slugInput = $('.page-head .url input');

var updateSlug = function(val)
{
	// Remove HTML tags
	val = val.replace("/<(.*?)>/g", '');

	// Make it lowercase
	val = val.toLowerCase();

	// Convert extended ASCII characters to basic ASCII
	val = blx.utils.asciiString(val);

	// Handle must start with a letter and end with a letter/number
	val = val.replace(/^[^a-z]+/, '');
	val = val.replace(/[^a-z0-9]+$/, '');

	// Get the "words"
	var words = blx.utils.filterArray(val.split(/[^a-z0-9]+/));
	val = words.join('-');

	$slug.html(val);
	$slugInput.val(val);
}

var onTitleChange = function()
{
	if (!$sidebarLabel)
		$sidebarLabel = $('#sidebar a.sel span.label');

	var val = titleInput.$input.val();
	if (val)
		$sidebarLabel.text(val);
	else
		$sidebarLabel.text('Untitled');

	// Update the slug
	if (autoUpdateSlug)
		updateSlug(val);
}

var titleInput = new blx.ui.TitleInput('.page-head .title', {
	onKeydown: function() {
		setTimeout(onTitleChange, 1);
	},
	onChange: onTitleChange
});


})(jQuery);
