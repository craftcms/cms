(function($) {


b.Entry = b.Base.extend({

	$container: null,
	entryId: null,
	draftId: null,

	$toolbar: null,
	$page: null,

	$autosaveStatus: null,
	$inputs: null,

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

		this.$autosaveStatus = this.$container.find('p.autosave-status:first');

		this.$inputs = $();
		this.changedInputs = {};

		this.$page.find('.nicetext').nicetext();

		// Find all of the form elements in the entry
		var $inputs = this.$page.find('input,textarea,select,button').filter(':input');
		this.initInput($inputs);
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


})(jQuery);
