(function($) {


var Content = b.Base.extend({

	$sidebarLinks: null,
	$selSidebarLink: null,
	$main: null,

	init: function()
	{
		this.$sidebarLinks = $('#sidebar a:not(.new)');
		this.$selSidebarLink = this.$sidebarLinks.filter('.sel:first');
		this.$main = $('#main');

		this.addListener(this.$sidebarLinks, 'click', 'onSidebarLinkClick');
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

		this.loadEntry(entryId);
	},

	loadEntry: function(entryId, draftId)
	{
		// Figure out which draft to show
		if (draftId === null && typeof localStorage != 'undefined')
			draftId = localStorage.getItem('lastDraftId:'+entryId);

		var data = {
			entryId: entryId,
			draftId: draftId
		};

		$.post(b.actionUrl+'content/loadEntryEditPage', data, $.proxy(function(response) {
			if (response.success)
			{
				this.$main.html(response.entryHtml);

				// Remember the draft id
				if (typeof localStorage != 'undefined')
				{
					if (response.draftId)
						localStorage.setItem('lastDraftId:'+entryId, response.draftId);
					else
						localStorage.removeItem('lastDraftId:'+entryId);
				}

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
