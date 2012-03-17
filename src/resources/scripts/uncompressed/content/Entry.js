(function($) {


b.Entry = b.Base.extend({

	$container: null,
	entryId: null,
	draftId: null,

	$toolbar: null,
	$page: null,

	$slug: null,
	$slugInput: null,
	$sidebarLabel: null,
	$autosaveStatus: null,
	$inputs: null,

	titleInput: null,
	urlInput: null,
	autoUpdateSlug: false,

	changedInputs: null,
	autosaveTimeout: null,

	init: function(container, entryId, draftId)
	{
		this.$container = $(container);
		this.entryId = entryId;
		this.draftId = draftId;

		this.$toolbar = this.$container.find('.toolbar:first');
		this.$page = this.$container.find('.page:first');

		this.$slug = this.$page.find('.page-head .slug');
		this.$slugInput = this.$page.find('.page-head .url input');
		this.$autosaveStatus = this.$container.find('p.autosave-status:first');

		this.$inputs = $();
		this.changedInputs = {};

		this.$page.find('.nicetext').nicetext();

		// Find all of the form elements in the entry
		var $inputs = this.$page.find('input,textarea,select,button').filter(':input').not(this.$slugInput);
		this.initInput($inputs);

		this.titleInput = new b.ui.TitleInput('.page-head .title', {
			onCreateInput: $.proxy(function(){
				this.initInput(this.titleInput.$input);
			}, this),
			onKeydown: $.proxy(function() {
				setTimeout($.proxy(this, 'onTitleChange'), 1);
			}, this),
			onChange: $.proxy(this, 'onTitleChange')
		});

		if (this.$slug.length)
		{
			if (!this.$slugInput.val())
				this.autoUpdateSlug = true;

			this.urlInput = new b.Entry.UrlInput(this, {
				onCreateInput: $.proxy(function() {
					this.autoUpdateSlug = false;
				}, this)
			});
		}

		
	},

	updateSlug: function(val)
	{
		// Remove HTML tags
		val = val.replace("/<(.*?)>/g", '');

		// Make it lowercase
		val = val.toLowerCase();

		// Convert extended ASCII characters to basic ASCII
		val = b.utils.asciiString(val);

		// Handle must start with a letter and end with a letter/number
		val = val.replace(/^[^a-z]+/, '');
		val = val.replace(/[^a-z0-9]+$/, '');

		// Get the "words"
		var words = b.utils.filterArray(val.split(/[^a-z0-9]+/));
		val = words.join('-');

		this.$slug.html(val);
		this.$slugInput.val(val);
	},

	onTitleChange: function()
	{
		if (!this.$sidebarLabel)
			this.$sidebarLabel = $('#sidebar a.sel span.label');

		var val = this.titleInput.$input.val();
		if (val)
			this.$sidebarLabel.text(val);
		else
			this.$sidebarLabel.text('Untitled');

		// Update the slug
		if (this.autoUpdateSlug)
			this.updateSlug(val);
	},

	initInput: function(input)
	{
		var $inputs = $(input);

		for (var i = 0; i < $inputs.length; i++)
		{
			var $input = $($inputs[i]);

			// Store the saved value
			$input.data('savedval', $input.val());

			// Listen for input changes
			this.addListener($input, 'change,keydown,keypress,click,blur', 'onInputChange');

			this.$inputs = this.$inputs.add($input);
		}
	},

	onInputChange: function(event)
	{
		// check again in 1ms if this was a keydown event
		if (event.type == 'keydown' && !event.dup)
		{
			setTimeout($.proxy(function() {
				var dupEvent = $.extend({}, event, {dup: true});
				this.onInputChange(dupEvent);
			}, this), 1);
			return;
		}

		// Has the value changed?
		var $input = $(event.currentTarget),
			savedVal = $input.data('savedval'),
			val = $input.val();

		if (val != savedVal)
		{
			clearTimeout(this.autosaveTimeout);
			$input.data('savedval', val);
			var inputName = $input.attr('name');
			this.changedInputs[inputName] = val;
			this.autosaveTimeout = setTimeout($.proxy(this, 'autosave'), b.Entry.autosaveDelay);
		}
	},

	autosave: function()
	{
		var data = $.extend({}, this.changedInputs, {
			entryId: this.entryId,
			draftId: this.draftId
		});

		// Pass the changed inputs
		var inputNames = [];
		for (var inputName in this.changedInputs)
		{
			data['changedInputs['+inputName+']'] = this.changedInputs[inputName];
		}

		this.changedInputs = {};

		$.post(b.actionUrl+'content/autosaveDraft', data, $.proxy(function(response) {
			if (response.success)
				this.$autosaveStatus.removeClass('error').text('Draft 1 autosaved a moment ago.');
			else
			{
				this.$autosaveStatus.addClass('error');
				if (response.error)
					this.$autosaveStatus.text(response.error);
				else
					this.$autosaveStatus.text('An unknown error occurred.');
			}
		}, this));
	}

}, {
	autosaveDelay: 2000
});


b.Entry.UrlInput = b.ui.TitleInput.extend({

	entry: null,
	$div: null,
	$prefix: null,

	init: function(entry, settings)
	{
		this.entry = entry;
		this.settings = $.extend({}, b.ui.TitleInput.defaults, b.Entry.UrlInput.defaults, settings);

		this.$container = this.entry.$page.find('.page-head .url .editable');
		this.$div = this.$container.find('div');
		this.$prefix = this.$div.find('.prefix');
		this.$heading = this.$div.find('.slug');
		this.$hiddenInput = this.$container.find('input');


		this.val = this.$hiddenInput.val();

		this.addListener(this.$container, 'focus,click', 'showInput');
	},

	createInput: function()
	{
		this.base();
		this.$input.removeClass('title-input').addClass('url-input');
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

}, {
	defaults: {
		emptyText: '…'
	}
});


})(jQuery);
