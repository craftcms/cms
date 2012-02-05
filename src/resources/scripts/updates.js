(function($) {


var Update = blx.Base.extend({

	dom: null,
	expanded: false,

	init: function(div, i)
	{
		this.dom = {};
		this.dom.$update = $(div);
		this.dom.$toggle = $('.notes-toggle', this.dom.$update);
		this.dom.$notesContainer = $('.notes-container', this.dom.$update);
		this.dom.$notes = $('.notes', this.dom.$notesContainer);

		this.addListener(this.dom.$toggle, 'click', 'toggle');

		if (location.hash && location.hash == '#'+this.dom.$update.attr('id'))
		{
			this.expand(false);

			// scroll to this update
			var scrollTo = this.dom.$update.offset().top - 54;
			$('html, body').animate({scrollTop: scrollTo});
		}

		this.dom.$update.delay(i*blx.fx.delay).animate({opacity: 1});
	},

	toggle: function()
	{
		if (!this.expanded)
			this.expand(true);
		else
			this.collapse(true);
	},

	expand: function(animate)
	{
		if (animate)
		{
			var height = this.dom.$notes.outerHeight();
			this.dom.$notesContainer.stop().animate({height: height}, $.proxy(function() {
				this.dom.$notesContainer.height('auto');
			}, this));
		}
		else
		{
			this.dom.$notesContainer.stop().height('auto');
		}

		this.dom.$toggle.html('Hide release notes');
		this.expanded = true;
	},

	collapse: function(animate)
	{
		if (animate)
		{
			this.dom.$notesContainer.stop().animate({height: 0});
		}
		else
		{
			this.dom.$notesContainer.stop().height(0);
		}

		this.dom.$toggle.html('Show release notes');
		this.expanded = false;
	}
});


var updatesUrl = baseUrl+'?p=settings/updates/updates';
$('#updates').load(updatesUrl, function() {
	var $sidebarLink = $('#sb-updates'),
		$sidebarBadge = $sidebarLink.find('span.badge'),
		$updatesContainer = $('#updates'),
		$updates = $updatesContainer.find('.update'),
		totalUpdates = $updates.length;

	// update the sidebar badge
	if (totalUpdates)
	{
		// create the badge if it doesn't exist
		if (!$sidebarBadge.length)
			$sidebarBadge = $('<span />').addClass('badge').appendTo($sidebarLink);

		$sidebarBadge.html(totalUpdates);
	}
	else
	{
		// delete the badge if it exists
		$sidebarBadge.remove();
	}

	// fade in the updates
	$('#checking').fadeOut();
	$updatesContainer.fadeIn(function() {
		$updates.each(function(i) {
			var update = new Update(this, i);
		});
	});
});


})(jQuery);
