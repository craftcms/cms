(function($) {


var CP = Blocks.Base.extend({

	$notificationContainer: null,
	$notifications: null,

	init: function()
	{
		// Fade the notification out in two seconds
		this.$notificationContainer = $('#notifications');
		this.$notifications = this.$notificationContainer.children();
		this.$notifications.delay(CP.notificationDuration).fadeOut();

		// Initialize the account menu button
		new Blocks.ui.MenuBtn('#account', {
			onOptionSelect: function(option) {
				var url = $(option).attr('data-url');
				document.location.href = Blocks.baseUrl + url;
			}
		});

		// Tabs
		$('.tabs').each(function() {
			var $container = $(this),
				$tabs = $container.find('a');
				$activeTab = $tabs.filter('.active:first');

			$tabs.click(function() {
				var $tab = $(this);
				if (this != $activeTab[0])
				{
					$activeTab.removeClass('active');
					var oldTarget = $activeTab.attr('data-target');

					$activeTab = $tab;
					$activeTab.addClass('active');
					var newTarget = $activeTab.attr('data-target');

					if (newTarget)
					{
						$('#'+newTarget).show();
					}

					if (oldTarget)
					{
						$('#'+oldTarget).hide();
					}
				}
			});
		});

		// Secondary form submit buttons
		$('.formsubmit').click(function() {
			var $btn = $(this),
				$form = $btn.closest('form');
			if ($btn.attr('data-action'))
				$('<input type="hidden" name="action" value="'+$btn.attr('data-action')+'"/>').appendTo($form);
			$form.submit();
		});
	},

	/**
	 * Dispays a notification.
	 *
	 * @param string type
	 * @param string message
	 */
	displayNotification: function(type, message)
	{
		$('<div class="notification '+type+'">'+message+'</div>')
			.appendTo(this.$notificationContainer)
			.fadeIn('fast')
			.delay(CP.notificationDuration)
			.fadeOut();
	},

	/**
	 * Displays a notice.
	 *
	 * @param string message
	 */
	displayNotice: function(message)
	{
		this.displayNotification('notice', message);
	},

	/**
	 * Displays an error.
	 *
	 * @param string message
	 */
	displayError: function(message)
	{
		this.displayNotification('error', message);
	}

}, {
	notificationDuration: 2000
});


Blocks.cp = new CP();


})(jQuery);
