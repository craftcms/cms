(function($) {


var Content = b.Base.extend({

	$addLinks: null,
	$entryLinks: null,
	$main: null,

	selEntry: null,
	$selEntryLink: null,

	ignoreNextStateChange: false,

	init: function()
	{
		this.$addLinks = $('#sidebar a.new');
		this.$entryLinks = $('#sidebar a:not(.new)');
		this.$selEntryLink = this.$entryLinks.filter('.sel:first');
		this.$main = $('#main');

		this.addListener(this.$addLinks, 'click', 'onNewEntryLinkClick');

		// If the browser supports history.pushState, load the pages over Ajax
		if (History.enabled)
		{
			this.addListener(b.$window, 'statechange', 'onStateChange');
			this.addListener(this.$entryLinks, 'click', 'onSidebarLinkClick');
		}
	},

	getEntryEditUrl: function(entryId, draftNum)
	{
		var url = b.baseUrl+'content/edit/'+entryId;
		if (draftNum)
			url += '/draft'+draftNum;
		return url;
	},

	onNewEntryLinkClick: function(event)
	{
		var $addLink = $(event.currentTarget),
			$li = $('<li/>').insertBefore($addLink.parent()),
			$inputWrapper = $('<div class="input-wrapper"/>').appendTo($li),
			$input = $('<input class="small" type="text" />').appendTo($inputWrapper);

		$addLink.hide();

		new b.ui.NiceText($input, {
			hint: 'Enter a title…'
		});

		this.addListener($input, 'keydown', function(event)
		{
			switch (event.keyCode)
			{
				case b.RETURN_KEY:

					event.preventDefault();
					var title = $input.val();
					if (title)
					{
						var $a = $('<a class="has-status sel"/>'),
							$status = $('<span class="status"/>').appendTo($a),
							$label = $('<span class="label">'+title+'</span>').appendTo($a);

						$inputWrapper.replaceWith($a);

						this.$selEntryLink.removeClass('sel');
						this.$selEntryLink = $a;

						if (History.enabled)
							this.addListener($a, 'click', 'onSidebarLinkClick');

						$addLink.show();

						var data = {
							sectionId: $addLink.attr('data-section-id'),
							title: title
						};

						$.post(b.actionUrl+'content/createEntry', data, $.proxy(function(response)
						{
							if (response.success)
							{
								var url = this.getEntryEditUrl(response.entryData.entryId, response.entryData.draftNum);

								if (History.enabled)
								{
									// Update the link
									$a.attr('href', url);
									$a.attr('data-entry-id', response.entryData.entryId);
									$label.text(response.entryData.entryTitle);

									// Load the entry's edit page
									this.loadEntry(response.entryData.entryId, response.entryData.draftNum, true);
								}
								else
									// Redirect to it
									window.location = url;
							}
							else
							{
								var error = (response.error || 'An unknown error occurred.');
								// show the error...
							}
						}, this));
					}

					break;

				case b.ESC_KEY:

					$li.remove();
					$addLink.show();
			}
		});

		$input.focus();
	},

	pushHistoryState: function(entryId, entryTitle, draftNum, draftName)
	{
		// Push the new history state
		if (History.enabled)
		{
			// Ignore the next state change event
			this.ignoreNextStateChange = true;

			var title = 'Editing “'+(entryTitle || 'Untitled')+'”';
			if (draftName)
				title += ' ('+draftName+')';

			var url = this.getEntryEditUrl(entryId, draftNum);
			History.pushState({entryId: entryId, draftNum: draftNum}, title, url);
		}

		// Remember which draft we're looking at
		if (typeof localStorage != 'undefined')
		{
			if (draftNum)
				localStorage.setItem('lastDraftNum:'+entryId, draftNum);
			else
				localStorage.removeItem('lastDraftNum:'+entryId);
		}
	},

	onStateChange: function()
	{
		if (this.ignoreNextStateChange)
		{
			this.ignoreNextStateChange = false;
			return;
		}

		var state = History.getState();

		// Update the selected link
		if (state.data.entryId != this.selEntry.data.entryId)
		{
			this.$selEntryLink.removeClass('sel');
			this.$selEntryLink = this.$entryLinks.filter('[data-entry-id='+state.data.entryId+']:first');
			this.$selEntryLink.addClass('sel');
		}

		this.loadEntry(state.data.entryId, state.data.draftNum);
	},

	getLastDraftNum: function(entryId)
	{
		if (typeof localStorage != 'undefined')
			return localStorage.getItem('lastDraftNum:'+entryId);
	},

	onSidebarLinkClick: function(event)
	{
		// Ignore if ctrl/cmd is pressed (they might want to open the enry in a new window)
		if (event.metaKey)
			return;

		event.preventDefault();

		var $link = $(event.currentTarget),
			entryId = $link.attr('data-entry-id');

		this.$selEntryLink.removeClass('sel');
		this.$selEntryLink = $link.addClass('sel');

		this.loadEntry(entryId, null, true);
	},

	loadEntry: function(entryId, draftNum)
	{
		// Figure out which draft to show
		if (draftNum === null)
			draftNum = this.getLastDraftNum(entryId);

		var data = {
			entryId:  entryId,
			draftNum: draftNum
		};

		$.post(b.actionUrl+'content/loadEntryEditPage', data, $.proxy(this, 'initEntry'));
	},

	createDraft: function(entryId, draftName)
	{
		var data = {
			entryId: entryId,
			draftName: draftName
		};

		$.post(b.actionUrl+'content/createDraft', data, $.proxy(this, 'initEntry'));
	},

	initEntry: function(response)
	{
		if (response.success)
		{
			// Load up the entry HTML
			this.$main.html(response.entryHtml);

			this.pushHistoryState(response.entryData.entryId, response.entryData.entryTitle, response.entryData.draftNum, response.entryData.draftName);

			// Initialize the entry
			this.selEntry = new b.Entry(this.$main, response.entryData);
		}
		else
		{
			var error = (response.error || 'An unknown error occurred.');
			alert(error);
		}
	}
});


b.content = new Content();


})(jQuery);
