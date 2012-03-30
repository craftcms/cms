(function($) {


b.Entry = b.Base.extend({

	$container: null,
	$toolbar: null,
	$versionSelect: null,
	$form: null,
	$page: null,
	$autosaveStatus: null,

	entryId: null,
	draftId: null,

	inputs: null,
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

		this.inputs = {};
		this.changedInputs = [];

		this.$page.find('.nicetext').nicetext();

		// Find all of the form elements in the entry
		var $inputs = this.$page.find('input,textarea,select,button').filter(':input');
		this.initInput($inputs);

		// Listen for version changes
		this.addListener(this.$versionSelect, 'change', function(event) {
			var val = this.$versionSelect.val();
			if (val != this.draftId && !(val == 'published' && !this.draftId))
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
							this.$versionSelect.val(this.draftId ? this.draftId : 'published');
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
			var $input   = $($inputs[i]),
				basename = b.getInputBasename($input),
				val      = b.getInputPostVal($input);

			// Store the saved value
			$input.data('savedval', val);

			// Listen for input changes
			this.addListener($input, 'change,keydown,keypress,click,blur', 'onInputChange');

			// Keep track of this input
			if (typeof this.inputs[basename] == 'undefined')
				this.inputs[basename] = [];
			this.inputs[basename].push($input);
		}
	},

	onInputChange: function(event, secondCall)
	{
		// Check again in 1ms if this was a keydown event
		if (event.type == 'keydown' && !secondCall)
		{
			setTimeout($.proxy(function() {
				this.onInputChange(event, true);
			}, this), 1);
			return;
		}

		// Has the value changed since the last time we saved?
		var $input   = $(event.currentTarget),
			val      = b.getInputPostVal($input),
			savedVal = $input.data('savedval'),
			changedInputsIndex  = $.inArray($input, this.changedInputs),
			changedInputsLength = this.changedInputs.length;

		if (val != savedVal && (!val || !savedVal || val.toString() != savedVal.toString()))
		{
			if (changedInputsIndex == -1)
				this.changedInputs.push($input);
		}
		else if (changedInputsIndex != -1)
		{
			// Remove it from the autosave queue
			this.changedInputs.splice(changedInputsIndex, 1);
		}

		// Push back the autosave timeout if something changed
		if (this.changedInputs.length != changedInputsLength)
		{
			clearTimeout(this.autosaveTimeout);
			this.autosaveTimeout = setTimeout($.proxy(this, 'autosaveDraft'), b.Entry.autosaveDelay);
		}
	},

	getSaveData: function()
	{
		var data = {entryId: this.entryId};

		if (this.draftId)
			data.draftId = this.draftId;

		var includedBasenames = [],
			sameArrayNameCount = {};

		// Pass the changed inputs
		for (var i = 0; i < this.changedInputs.length; i++)
		{
			var $input   = this.changedInputs[i],
				basename = b.getInputBasename($input);

			// Have we already included this input? (Possible if it shares the same basename with a previous input)
			if (b.inArray(basename, includedBasenames))
				continue;

			// Loop through all inputs that share the same basename, disregarding the original $input
			for (var j = 0; j < this.inputs[basename].length; j++)
			{
				var $input = this.inputs[basename][j],
					val    = b.getInputPostVal($input);

				// Update the input's savedval record
				$input.data('savedval', $input.val());

				// Skip this input if its value is null
				if (val === null)
					continue;

				var inputName = b.namespaceInputName($input.attr('name'), 'content[blocks]'),
					arrayName = (inputName.substr(-2) == '[]');

				if (arrayName)
				{
					// Chop off the brackets at the end
					inputName = inputName.replace(/\[\]$/, '');

					// Keep track of all like-named inputs
					if (typeof sameArrayNameCount[inputName] == 'undefined')
						sameArrayNameCount[inputName] = 0;
				}

				if (b.isArray(val))
				{
					for (var k = 0; k < val.length; k++)
					{
						if (arrayName)
						{
							sameArrayNameCount[inputName]++;
							data[inputName+'['+sameArrayNameCount[inputName]+']'] = val[k];
						}
						else
							data[inputName] = val[k];
					}
				}
				else
				{
					if (arrayName)
					{
						sameArrayNameCount[inputName]++;
						data[inputName+'['+sameArrayNameCount[inputName]+']'] = val;
					}
					else
						data[inputName] = val;
				}
			}

			// Remember that we've already included all inputs with this basename
			includedBasenames.push(basename);
		}

		// Reset the changed inputs record
		this.changedInputs = [];

		return data;
	},

	autosaveDraft: function()
	{
		// Make sure there's actually something to save
		if (!this.changedInputs.length)
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
	autosaveDelay: 1000
});


})(jQuery);
