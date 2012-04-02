(function($) {


b.Entry = b.Base.extend({

	$container: null,
	$toolbar: null,
	$versionSelect: null,
	$statusIcon: null,
	$statusLabel: null,
	$form: null,
	$page: null,
	$autosaveStatus: null,

	data: null,

	inputs: null,
	changedInputs: null,
	waiting: false,
	onAjaxResponse: null,

	autosaveTimeout: null,

	/**
	 * Initilaizes the entry.
	 */
	init: function(container, data)
	{
		this.$container = $(container);
		this.$form = this.$container.find('form:first');
		this.data = data;

		this.$toolbar = this.$container.find('.toolbar:first');
		this.$versionSelect = this.$toolbar.find('.versionselect:first');

		var $statusContainer = this.$toolbar.find('div.status:first');
		this.$statusIcon = $statusContainer.children('.status:first');
		this.$statusLabel = $statusContainer.children('.label:first');

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
			// Has it actually changed to something we didn't expect?
			var val = this.$versionSelect.val();
			if (val != this.data.draftId && !(val == 'published' && !this.data.draftId))
			{
				switch (val)
				{
					case 'published':
						b.content.loadEntry(this.data.entryId, false);
						break;
					case 'new':
						var draftName = prompt('Give your new draft a name.');
						if (draftName)
							b.content.createDraft(this.data.entryId, draftName);
						else
							this.$versionSelect.val(this.data.draftId ? this.data.draftId : 'published');
						break;
					default:
						b.content.loadEntry(this.data.entryId, val);
				}
			}
		});

		// Listen for submits
		this.addListener(this.$form, 'submit', function(event) {
			event.preventDefault();
			this.publishDraft();
		});
	},

	/**
	 * Initializes an input (stores its current value, listens for changes).
	 * @param mixed input Either an actual element or a jQuery collection.
	 */
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

	/**
	 * Determines whether an input's value has changed since the last autosave,
	 * adding or removing it from the changedInputs array if necessary.
	 * @param object event The input's event object
	 * @param bool secondCall Keydown events will use this param to signify that it's being called for the second time, after a 1ms delay.
	 */
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

	/**
	 * Returns all of the data necessary for saving changes to the entry
	 * @return array
	 */
	getSaveData: function()
	{
		var data = {entryId: this.data.entryId};

		if (this.data.draftId)
			data.draftId = this.data.draftId;

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

	/**
	 * Autosaves the current draft
	 */
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
				// Was a new draft just created?
				var isNewDraft = (response.entryData.draftId != this.data.draftId);

				this.data = response.entryData;

				if (isNewDraft)
				{
					// Add the new draft's option at the end of the version select
					var $newDraftOption = this.$versionSelect.find('[value=new]:first'),
						$option = $('<option value="'+this.data.draftId+'">“'+this.data.draftName+'” by '+this.data.draftAuthor+'</option>').insertBefore($newDraftOption);

					this.$versionSelect.val(this.data.draftId);

					b.content.pushHistoryState(this.data.entryId, this.data.entryTitle, this.data.draftNum, this.data.draftName);
				}

				// Is something queued up?
				if (this.onAjaxResponse)
				{
					this[this.onAjaxResponse]();
					this.onAjaxResponse = null;
				}

				this.$autosaveStatus.removeClass('error').text(this.data.draftName+' autosaved a moment ago.');
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

	/**
	 * Publishes the current draft
	 */
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
				// If we were editing a draft, remove it from the version select
				if (this.data.draftNum)
					this.$versionSelect.find('[value='+this.data.draftNum+']:first').remove();

				this.data = response.entryData;

				// Try to find the Published version option
				var $publishedVersionOption = this.$versionSelect.find('[value=published]:first');

				// Create it if this is the first time the entry has been published
				if (!$publishedVersionOption.length)
					$publishedVersionOption = $('<option value="published">Published</option>').prependTo(this.$versionSelect);

				this.$versionSelect.val('published');

				this.updateEntryStatus();
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
	},

	/**
	 * Updates the entry's status indicator
	 */
	updateEntryStatus: function()
	{
		switch (this.data.entryStatus)
		{
			case 'live':
				var statusClass = 'on',
					statusText = 'Live';
				break;

			case 'pending':
				var statusClass = 'pending',
					statusText = 'Pending';
				break;

			case 'expired':
				var statusClass = 'off',
					statusText = 'Expired';
				break;

			default:
				var statusClass = '',
					statusText = 'Never Published';
		}

		b.content.$selEntryLink.find('.status:first').prop('className', 'status '+statusClass);
		this.$statusIcon.prop('className', 'status '+statusClass);
		this.$statusLabel.text(statusText);
	}

}, {
	autosaveDelay: 1000
});


})(jQuery);
