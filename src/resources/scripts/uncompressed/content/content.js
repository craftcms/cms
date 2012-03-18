(function($) {


var Content = b.Base.extend({

	$sidebarLinks: null,
	$selSidebarLink: null,
	$main: null,

	entry: null,
	ignoreStateChange: false,

	init: function()
	{
		this.$sidebarLinks = $('#sidebar a:not(.new)');
		this.$selSidebarLink = this.$sidebarLinks.filter('.sel:first');
		this.$main = $('#main');

		// If the browser supports history.pushState, load the pages over Ajax
		if (History.enabled)
		{
			this.addListener(b.$window, 'statechange', 'onStateChange');
			this.addListener(this.$sidebarLinks, 'click', 'onSidebarLinkClick');
		}
	},

	onStateChange: function()
	{
		if (this.ignoreStateChange)
		{
			this.ignoreStateChange = false;
			return;
		}

		var state = History.getState();

		// Update the selected link
		if (state.data.entryId != this.entry.entryId)
		{
			this.$selSidebarLink.removeClass('sel');
			this.$selSidebarLink = this.$sidebarLinks.filter('[data-entry-id='+state.data.entryId+']:first');
			this.$selSidebarLink.addClass('sel');
		}

		this.loadEntry(state.data.entryId, state.data.draftId);
	},

	getLastDraft: function(entryId)
	{
		if (typeof localStorage != 'undefined')
			return localStorage.getItem('lastDraftId:'+entryId);
	},

	onSidebarLinkClick: function(event)
	{
		// Ignore if ctrl/cmd is pressed (they might want to open the enry in a new window)
		if (event.metaKey)
			return;

		event.preventDefault();

		var $link = $(event.currentTarget),
			entryId = $link.attr('data-entry-id');

		this.$selSidebarLink.removeClass('sel');
		this.$selSidebarLink = $link.addClass('sel');

		this.loadEntry(entryId, null, true);
	},

	loadEntry: function(entryId, draftId, pushState)
	{
		// Figure out which draft to show
		if (draftId === null)
			draftId = this.getLastDraft(entryId);

		var data = {
			entryId: entryId,
			draftId: draftId
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
					this.ignoreStateChange = true;

					var title = 'Editing “'+(response.entryTitle || 'Untitled')+'”';
					if (response.draftName)
						title += ' ('+response.draftName+')';

					var url = b.baseUrl+'content/edit/'+entryId;
					if (response.draftId)
						url += '/draft'+draftId;
					History.pushState({entryId: entryId, draftId: response.draftId}, title, url);
				}

				// Remember the draft id
				if (typeof localStorage != 'undefined')
				{
					if (response.draftId)
						localStorage.setItem('lastDraftId:'+entryId, response.draftId);
					else
						localStorage.removeItem('lastDraftId:'+entryId);
				}

				// Initialize the entry
				this.entry = new b.Entry(this.$main, entryId, response.draftId);
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
