(function($) {

Blocks.Installer = Blocks.Base.extend({

	$screens: null,
	$currentScreen: null,

	$accountSubmitBtn: null,
	$siteSubmitBtn: null,

	loading: false,

	/**
	* Constructor
	*/
	init: function()
	{
		this.$screens = Blocks.$body.children('.modal');

		setTimeout($.proxy(this, 'showWelcomeScreen'), 500);

		// Preload the spinner image
		var spinner = new Image;
		spinner.src = Blocks.resourceUrl+'images/submit-spinner.gif';
	},

	showWelcomeScreen: function()
	{
		this.$currentScreen = $(this.$screens[0])
			.removeClass('scaleddown')
			.animate({opacity: 1}, 'fast', $.proxy(function() {
				this.addListener($('#getstarted'), 'click', 'showAccountScreen');

				/* HIDE */
				// Give the License Key input focus after half a second
				this.focusFirstInput();

				// Get ready for form submit
				this.$licensekeySubmitBtn = $('#licensekeysubmit');
				this.addListener(this.$licensekeySubmitBtn, 'click', 'validateLicenseKey');
				this.addListener($('#licensekeyform'), 'submit', 'validateLicenseKey');
				/* end HIDE */
			}, this));
	},

	/* HIDE */
	validateLicenseKey: function(event)
	{
		event.preventDefault();

		var inputs = ['licensekey'];
		this.validate('licensekey', inputs, $.proxy(this, 'showAccountScreen'));
	},
	/* end HIDE */

	showAccountScreen: function(event)
	{
		this.showScreen(1, $.proxy(function() {
			this.$accountSubmitBtn = $('#accountsubmit');
			this.addListener(this.$accountSubmitBtn, 'click', 'validateAccount');
			this.addListener($('#accountform'), 'submit', 'validateAccount');
		}, this));
	},

	validateAccount: function(event)
	{
		event.preventDefault();

		var inputs = ['username', 'email', 'password'];
		this.validate('account', inputs, $.proxy(this, 'showSiteScreen'));
	},

	showSiteScreen: function()
	{
		this.showScreen(2, $.proxy(function() {
			this.$siteSubmitBtn = $('#sitesubmit');
			this.addListener(this.$siteSubmitBtn, 'click', 'validateSite');
			this.addListener($('#siteform'), 'submit', 'validateSite');
		}, this));
	},

	validateSite: function(event)
	{
		event.preventDefault();

		var inputs = ['siteName', 'siteUrl'];
		this.validate('site', inputs, $.proxy(this, 'showInstallScreen'));
	},

	showInstallScreen: function()
	{
		this.showScreen(3, $.proxy(function() {

			var inputs = ['username', 'email', 'password', 'siteName', 'siteUrl', 'language'];
			/* HIDE */
			var inputs = ['licensekey', 'username', 'email', 'password', 'siteName', 'siteUrl', 'language'];
			/* end HIDE */

			var data = {};

			for (var i = 0; i < inputs.length; i++)
			{
				var input = inputs[i],
					$input = $('#'+input);

				data[input] = Blocks.getInputPostVal($input);
			}

			$.post(Blocks.actionUrl+'install/install', data, $.proxy(function() {
				this.$currentScreen.find('h1:first').text(Blocks.t('All done!'));
				var $buttons = $('<div class="buttons"><a href="'+Blocks.baseUrl+'dashboard" class="btn big submit">'+Blocks.t("Go to Blocks")+'</a></div>');
				$('#spinner').replaceWith($buttons);
			}, this));

		}, this));
	},

	showScreen: function(i, callback)
	{
		// Slide out the old screen
		var windowWidth = Blocks.$window.width(),
			centeredLeftPos = Math.floor(windowWidth / 2);

		this.$currentScreen
			.css('left', centeredLeftPos)
			.animate({
				left: -730,
				opacity: 0
			});

		// Slide in the new screen
		this.$currentScreen = $(this.$screens[i])
			.css({
				display: 'block',
				left: windowWidth + 370
			})
			.animate({left: centeredLeftPos}, $.proxy(function() {
				// Relax the screen
				this.$currentScreen.css('left', '50%');

				// Give focus to the first input
				this.focusFirstInput();

				// Call the callback
				callback();
			}, this));
	},

	validate: function(what, inputs, callback)
	{
		// Prevent double-clicks
		if (this.loading)
			return;

		this.loading = true;

		// Clear any previous error lists
		$('#'+what+'form').find('.errors').remove();

		var $submitBtn = this['$'+what+'SubmitBtn'];
		$submitBtn.addClass('sel loading');

		var url = Blocks.actionUrl+'install/validate'+Blocks.uppercaseFirst(what);

		var data = {};
		for (var i = 0; i < inputs.length; i++)
		{
			var input = inputs[i],
				$input = $('#'+input);
			data[input] = Blocks.getInputPostVal($input);
		}

		$.post(url, data, $.proxy(function(response) {
			if (response.validates)
				callback();
			else
			{
				for (var input in response.errors)
				{
					var errors = response.errors[input],
						$input = $('#'+input),
						$field = $input.closest('.field'),
						$ul = $('<ul class="errors"/>').appendTo($field);

					for (var i = 0; i < errors.length; i++)
					{
						var error = errors[i];
						$('<li>'+error+'</li>').appendTo($ul);
					}

					if (!$input.is(':focus'))
					{
						$input.addClass('error');
						($.proxy(function($input) {
							this.addListener($input, 'focus', function() {
								$input.removeClass('error');
								this.removeListener($input, 'focus');
							});
						}, this))($input);
					}
				}

				Blocks.shake(this.$currentScreen);
			}

			this.loading = false;
			$submitBtn.removeClass('sel loading');
		}, this));
	},

	focusFirstInput: function()
	{
		setTimeout($.proxy(function() {
			this.$currentScreen.find('input:first').focus();
		}, this), 400);
	}

});

Blocks.$window.on('load', function() {
	Blocks.installer = new Blocks.Installer();
});

})(jQuery);
