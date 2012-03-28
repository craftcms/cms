(function($) {


b.Entry = b.Base.extend({

	$container: null,
	$toolbar: null,
	$versionSelect: null,
	$form: null,
	$page: null,
	$autosaveStatus: null,
	$inputs: null,

	entryId: null,
	draftId: null,
	viewing: null,

	changedInputs: null,
	waiting: false,
	onAjaxResponse: null,

	autosaveTimeout: null,

	init: function(container, entryId, draftId)
	{
		this.$container = $(container);
		this.$form = this.$container.find('form:first');
		this.entryId = entryId;
		this.draftId = draftId;

		this.$toolbar = this.$container.find('.toolbar:first');
		this.$versionSelect = this.$toolbar.find('.versionselect:first');
		this.$page = this.$form.find('.page:first');
		this.$autosaveStatus = this.$form.find('p.autosave-status:first');

		this.viewing = this.$versionSelect.val();

		this.$inputs = $();
		this.changedInputs = {};

		this.$page.find('.nicetext').nicetext();

		// Find all of the form elements in the entry
		var $inputs = this.$page.find('input,textarea,select,button').filter(':input');
		this.initInput($inputs);

		// Listen for version changes
		this.addListener(this.$versionSelect, 'change', function(event) {
			var val = this.$versionSelect.val();
			if (val != this.viewing)
			{
				switch (val)
				{
					case 'published':
						b.content.loadEntry(this.entryId, false);
						break;
					case 'new':
						var draftName = prompt('Give your new draft a name.')
						if (draftName)
							b.content.createDraft(this.entryId, draftName);
						else
							this.$versionSelect.val(this.viewing);
						break;
					default:
						b.content.loadEntry(this.entryId, val);
				}
			}
		});

		// Listen for submits
		this.addListener(this.$form, 'submit', function(event) {
			event.preventDefault();
			this.publishDraft();
		});
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
			this.autosaveTimeout = setTimeout($.proxy(this, 'autosaveDraft'), b.Entry.autosaveDelay);
		}
		else if (typeof this.changedInputs[inputName] != 'undefined')
		{
			delete this.changedInputs[inputName];
		}
	},

	getSaveData: function()
	{
		var data = {entryId: this.entryId};

		if (this.draftId)
			data.draftId = this.draftId;

		// Pass the changed inputs
		for (var inputName in this.changedInputs)
		{
			data['content['+inputName+']'] = this.changedInputs[inputName];
		}

		this.changedInputs = {};

		return data;
	},

	autosaveDraft: function()
	{
		// Make sure there's actually something to save
		var autosave = false;
		for (var i in this.changedInputs)
		{
			autosave = true;
			break;
		}
		if (!autosave)
			return;

		// Only autosave once at a time
		if (this.waiting)
		{
			this.onAjaxResponse = 'autosaveDraft';
			return;
		}

		this.waiting = true;

		$.post(b.actionUrl+'content/autosaveDraft', this.getSaveData(), $.proxy(function(response) {
			if (response.success)
			{
				this.$autosaveStatus.removeClass('error').text(response.draftName+' autosaved a moment ago.');

				// New draft?
				if (!this.draftId)
				{
					this.draftId = response.draftId;
					var $newDraftOption = this.$versionSelect.find('[value=new]')
						$option = $('<option value="'+response.draftId+'">“'+response.draftName+'” by '+response.draftAuthor+'</option>').insertBefore($newDraftOption);
					this.$versionSelect.val(response.draftId);

					b.content.pushHistoryState(response.entryId, response.entryTitle, response.draftNum, response.draftName);
				}

				// Is something queued up?
				if (this.onAjaxResponse)
				{
					this[this.onAjaxResponse]();
					this.onAjaxResponse = null;
				}
			}
			else
			{
				this.$autosaveStatus.addClass('error');
				if (response.error)
					this.$autosaveStatus.text(response.error);
				else
					this.$autosaveStatus.text('An unknown error occurred.');
			}

			this.waiting = false;
		}, this));
	},

	publishDraft: function()
	{
		// Prevent autosaves
		clearTimeout(this.autosaveTimeout);

		// Only autosave once at a time
		if (this.waiting)
		{
			this.onAjaxResponse = 'publishDraft';
			return;
		}

		this.waiting = true;

		$.post(b.actionUrl+'content/publishDraft', this.getSaveData(), $.proxy(function(response) {
			if (response.success)
			{
				b.content.loadEntry(this.entryId, false);
			}
			else
			{
				if (response.error)
					alert(response.error);
				else
					alert('An unknown error occurred.');
			}

			this.waiting = false;
		}, this));
	}

}, {
	autosaveDelay: 2000
});


})(jQuery);
