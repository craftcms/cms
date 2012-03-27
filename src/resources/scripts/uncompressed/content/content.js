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
								var url = this.getEntryEditUrl(response.entryId, response.draftNum);

								if (History.enabled)
								{
									// Update the link
									$a.attr('href', url);
									$a.attr('data-entry-id', response.entryId);
									$label.text(response.entryTitle);

									// Load the entry's edit page
									this.loadEntry(response.entryId, response.draftNum, true);
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

	onStateChange: function()
	{
		if (this.ignoreNextStateChange)
		{
			this.ignoreNextStateChange = false;
			return;
		}

		var state = History.getState();

		// Update the selected link
		if (state.data.entryId != this.selEntry.entryId)
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

	loadEntry: function(entryId, draftNum, pushState)
	{
		// Figure out which draft to show
		if (draftNum === null)
			draftNum = this.getLastDraftNum(entryId);

		var data = {
			entryId:  entryId,
			draftNum: draftNum
		};

		$.post(b.actionUrl+'content/loadEntryEditPage', data, $.proxy(function(response) {
			if (response.success)
			{
				// Load up the entry HTML
				this.$main.html(response.entryHtml);

				// Change the History state
				if (pushState && History.enabled)
				{
					// Ignore the next state change event
					this.ignoreNextStateChange = true;

					var title = 'Editing “'+(response.entryTitle || 'Untitled')+'”';
					if (response.draftName)
						title += ' ('+response.draftName+')';

					var url = this.getEntryEditUrl(entryId, draftNum);
					History.pushState({entryId: entryId, draftNum: response.draftNum}, title, url);
				}

				// Remember the draft id
				if (typeof localStorage != 'undefined')
				{
					if (response.draftNum)
						localStorage.setItem('lastDraftNum:'+entryId, response.draftNum);
					else
						localStorage.removeItem('lastDraftNum:'+entryId);
				}

				// Initialize the entry
				this.selEntry = new b.Entry(this.$main, entryId, response.draftId);
			}
			else
			{
				var error = (response.error || 'An unknown error occurred.');
				// show the error...
			}
		}, this));
	}
});


b.content = new Content();


})(jQuery);
