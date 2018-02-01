(function($) {

Craft.Installer = Garnish.Base.extend(
{
	$bg: null,
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
		this.$bg = $('#bg');
		this.$screens = Garnish.$bod.children('.modal');

		this.addListener($('#beginbtn'), 'activate', 'showAccountScreen');
	},

	showAccountScreen: function(event)
	{
		this.showScreen(1, $.proxy(function() {
			$('#beginbtn').remove();
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
		this.showScreen(3, $.proxy(function()
		{
			var inputs = ['username', 'email', 'password', 'siteName', 'siteUrl', 'locale'];

			var data = {};

			for (var i = 0; i < inputs.length; i++)
			{
				var input = inputs[i],
					$input = $('#'+input);

				data[input] = Garnish.getInputPostVal($input);
			}

			Craft.postActionRequest('install/install', data, $.proxy(this, 'allDone'), {
				complete: $.noop
			});

		}, this));
	},

	allDone: function(response, textStatus)
	{
		if (textStatus == 'success' && response.success)
		{
			this.$currentScreen.find('h1:first').text(Craft.t('All done!'));

			var $buttons = $('<div class="buttons"/>'),
				$go = $('<div class="btn big submit">'+Craft.t('Go to Craft CMS')+'</div>').appendTo($buttons);

			$('#spinner').replaceWith($buttons);

			this.addListener($go, 'click', function() {
				this.showScreen(30, null, 1000);

				setTimeout(function() {
					window.location.href = Craft.getUrl('dashboard');
				}, Craft.Installer.duration);
			});
		}
		else
		{
			this.$currentScreen.find('h1:first').text('Oops.');
		}
	},

	showScreen: function(i, callback, bgDuration)
	{
		if (!bgDuration)
		{
			bgDuration = Craft.Installer.duration;
		}

		// Slide the BG
		this.$bg.velocity({ left: '-'+(i*5)+'%' }, bgDuration);

		// Slide out the old screen
		var windowWidth = Garnish.$win.width(),
			centeredLeftPos = Math.floor(windowWidth / 2);

		if (this.$currentScreen)
		{
			this.$currentScreen
				.css('left', centeredLeftPos)
				.velocity({ left: -400 }, Craft.Installer.duration);
		}

		// Slide in the new screen
		this.$currentScreen = $(this.$screens[i-1])
			.css({
				display: 'block',
				left: windowWidth + 400
			})
			.velocity({ left: centeredLeftPos }, Craft.Installer.duration, $.proxy(function()
			{
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

		Craft.postActionRequest(action, data, $.proxy(function(response, textStatus)
		{
			this.loading = false;
			$submitBtn.removeClass('sel loading');

			if (textStatus == 'success')
			{
				if (response.validates)
				{
					callback();
				}
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
			}

		}, this));
	},

	focusFirstInput: function()
	{
		setTimeout($.proxy(function() {
			this.$currentScreen.find('input:first').trigger('focus');
		}, this), Craft.Installer.duration);
	}

},
{
	duration: 300
});

Garnish.$win.on('load', function() {
	Craft.installer = new Craft.Installer();
});

})(jQuery);
