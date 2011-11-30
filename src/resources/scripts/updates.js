(function($) {


function hideSpinner()
{
	$('#checking').fadeOut();
	setTimeout(showUpdates, 200);
}

function showUpdates()
{
	$('#updates').fadeIn(function() {
		var $updates = $('.update', this);
		$updates.each(function(i) {
			var $update = $(this);
			setTimeout(function() {
				$update.fadeIn(initUpdate);
			}, i * 100);
		});
	});
}

function initUpdate()
{
	var $update = $(this),
		$toggle = $('.notes-toggle', $update),
		$notes = $('.notes', $update),
		expanded = false;

	$toggle.click(function() {
		if (!expanded)
		{
			var collapsedHeight = $update.height();
			$notes.show();
			var expandedHeight = $update.height();
			$update.height(collapsedHeight);
			$update.stop().animate({height: expandedHeight}, function() {
				$update.height('auto');
			});
			$toggle.html('Hide release notes');
		}
		else
		{
			$notes.hide();
			var collapsedHeight = $update.height();
			$notes.show();
			$update.stop().animate({height: collapsedHeight}, function() {
				$update.height('auto');
				$notes.hide();
			});
			$toggle.html('Show release notes');
		}

		expanded = !expanded;
	});
}

setTimeout(hideSpinner, 1000);


})(jQuery);
