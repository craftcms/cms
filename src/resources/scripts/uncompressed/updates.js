(function($) {


// Keep the spinner vertically centered
b.$window.on('resize.updates', centerSpinner);


var updatesUrl = b.baseUrl+'settings/updates/updates';
$('#updates').load(updatesUrl, function()
{
	b.$window.off('resize.updates');

	var $sidebarLink = $('#sb-updates'),
		$sidebarBadge = $sidebarLink.find('span.badge'),
		$updatesContainer = $('#updates'),
		totalUpdates = $updatesContainer.find('tr').length;

	if (totalUpdates)
	{
		// create the sidebar badge if it doesn't exist
		if (!$sidebarBadge.length)
			$sidebarBadge = $('<span />').addClass('badge').appendTo($sidebarLink);

		$sidebarBadge.html(totalUpdates);

		// initialize the modals
		var $noteLinks = $updatesContainer.find('a.notes');
		if ($noteLinks.length)
		{
			var $pane = $('<div class="pane modal"/>').appendTo(b.$body),
				$head = $('<div class="pane-head"><h5>Release Notes</h5></div>').appendTo($pane),
				$body = $('<div class="pane-body scrollpane"/>').appendTo($pane),
				$item = $('<div class="pane-item"/>').appendTo($body),
				$foot = $('<div class="pane-foot"/>').appendTo($pane),
				$btn  = $('<div class="btn close"><span class="label">Close</span></div>').appendTo($foot);

			var noteModal = new b.ui.Modal($pane);

			$noteLinks.click(function() {
				var $link = $(this),
					$notes = $link.next();
				$item.html($notes.html());
				noteModal.show();
				noteModal.centerInViewport();
			});
		}
	}
	else
	{
		// delete the badge if it exists
		$sidebarBadge.remove();

		// Keep the "no updates" dialog vertically centered
		$dialog = $('#no-updates');
		var centerDialog = function()
		{
			var top = ((window.innerHeight-48) / 2) - 43;
			$dialog.css('top', top);
		}
		centerDialog();
		b.$window.on('resize', centerDialog);
	}

	// fade in the updates
	$('#checking').fadeOut();
	$updatesContainer.fadeIn();
});


})(jQuery);
