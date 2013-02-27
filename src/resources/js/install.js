(function($) {

Craft.Installer = Garnish.Base.extend({

	$screens: null,
	$currentScreen: null,

	$accountSubmitBtn: null,
	$siteSubmitBtn: null,

	loading: false,
	installing: null,
	gettingLicenseKey: null,
	licenseKey: null,

	/**
	* Constructor
	*/
	init: function()
	{
		this.$screens = Garnish.$bod.children('.modal');

		setTimeout($.proxy(this, 'showWelcomeScreen'), 500);
	},

	showWelcomeScreen: function()
	{
		this.$currentScreen = $(this.$screens[0])
			.removeClass('scaleddown')
			.animate({opacity: 1}, 'fast', $.proxy(function() {
				this.addListener($('#getstartedbtn'), 'activate', 'showAccountScreen');

				// Give the License Key input focus after half a second
				this.focusFirstInput();

				// Get ready for form submit
				this.addListener($('#getstartedbtn'), 'activate', 'validateLicenseKey');
			}, this));
	},

	showAccountScreen: function(event)
	{
		this.showScreen(1, $.proxy(function() {
			this.$accountSubmitBtn = $('#accountsubmit');
			this.addListener(this.$accountSubmitBtn, 'activate', 'validateAccount');
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
			this.addListener(this.$siteSubmitBtn, 'activate', 'validateSite');
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

			var inputs = ['username', 'email', 'password', 'siteName', 'siteUrl', 'locale'];

			var data = {};

			for (var i = 0; i < inputs.length; i++)
			{
				var input = inputs[i],
					$input = $('#'+input);

				data[input] = Garnish.getInputPostVal($input);
			}

			this.installing = true;
			this.gettingLicenseKey = true;

			Craft.postActionRequest('install/install', data, $.proxy(function() {
				this.installing = false;
				this.allDone();
			}, this));

			// While we're waiting, see if we can't generate a license key...
			var data = {
				email: $('#email').val()
			};

			$.ajax({
				url:     '@@@elliottEndpointUrl@@@actions/licenses/createLicense',
				data:    data,
				type:    'POST',

				success: $.proxy(function(response)
				{
					if (response.success)
					{
						this.licenseKey = response.licenseKey;
					}

					this.gettingLicenseKey = false;
					this.allDone();
				}, this),

				error:   $.proxy(function()
				{
					this.gettingLicenseKey = false;
					this.allDone();
				}, this)
			});

		}, this));
	},

	allDone: function()
	{
		if (!this.installing && !this.gettingLicenseKey)
		{
			if (this.licenseKey)
			{
				// Save it
				var data = {
					licenseKey: this.licenseKey
				};

				Craft.postActionRequest('systemSettings/saveLicenseKey', data, $.proxy(this, 'showAllDone'));
			}
			else
			{
				this.showAllDone();
			}
		}
	},

	showAllDone: function()
	{
		this.$currentScreen.find('h1:first').text(Craft.t('All done!'));
		var $buttons = $('<div class="buttons"><a href="'+Craft.getUrl('dashboard')+'" class="btn big submit">'+Craft.t('Go to @@@appName@@@')+'</a></div>');
		$('#spinner').replaceWith($buttons);
	},

	showScreen: function(i, callback)
	{
		// Slide out the old screen
		var windowWidth = Garnish.$win.width(),
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

		var action = 'install/validate'+Craft.uppercaseFirst(what);

		var data = {};
		for (var i = 0; i < inputs.length; i++)
		{
			var input = inputs[i],
				$input = $('#'+input);
			data[input] = Garnish.getInputPostVal($input);
		}

		Craft.postActionRequest(action, data, $.proxy(function(response) {
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

				Garnish.shake(this.$currentScreen);
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

Garnish.$win.on('load', function() {
	Craft.installer = new Craft.Installer();
});

})(jQuery);
