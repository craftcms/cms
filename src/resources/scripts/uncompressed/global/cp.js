(function($) {


var CP = blx.Base.extend({

	$notificationContainer: null,
	$notifications: null,

	init: function()
	{
		// Fade the notification out in two seconds
		this.$notificationContainer = $('#notifications');
		this.$notifications = this.$notificationContainer.children();
		this.$notifications.delay(CP.notificationDuration).fadeOut();

		// Initialize the account menu button
		new blx.ui.MenuBtn('#account', {
			onOptionSelect: function(option) {
				var url = $(option).attr('data-url');
				document.location.href = blx.baseUrl + url;
			}
		});

		// Secondary form submit buttons
		$('.formsubmit').click(function() {
			var $btn = $(this),
				$form = $btn.closest('form');
			if ($btn.attr('data-action'))
				$('<input type="hidden" name="action" value="'+$btn.attr('data-action')+'"/>').appendTo($form);
			$form.submit();
		});
	}

}, {
	notificationDuration: 2000
});


var cp = new CP();


})(jQuery);
