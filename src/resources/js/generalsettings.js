(function($) {


Craft.LicenseKeyForm = Garnish.Base.extend({

	$input: null,
	$newKeyBtn: null,
	$spinner: null,

	loading: false,

	/**
	* Constructor
	*/
	init: function(settings)
	{
		this.setSettings(settings);

		this.$input = $('#licenseKey');
		this.$newKeyBtn = $('#newkeybtn');
		this.$spinner = $('#licensekeyspinner');

		this.addListener(this.$newKeyBtn, 'activate', 'getNewLicenseKey');
	},

	getNewLicenseKey: function()
	{
		if (this.loading)
		{
			return;
		}

		this.loading = true;
		this.$newKeyBtn.addClass('active');
		this.$spinner.removeClass('hidden');

		var data =
			JSON.stringify({
				requestUrl: document.location.href,
				requestIp: '1.1.1.1',
				data: this.settings.email
		});

		$.ajax({
			url:     'http://elliott.buildwithcraft.dev/actions/elliott/app/createLicense',
			data:    data,
			type:    'POST',
			dataType: 'json',

			success: $.proxy(function(response)
			{
				this.onLicenseKeyResponse();

				if (response.errors)
				{
					this.onUnsuccessfulLicenseKeyResponse();
				}
				else
				{
					this.$input.val(response.data);
				}
			}, this),

			error:   $.proxy(function()
			{
				this.onLicenseKeyResponse();
				this.onUnsuccessfulLicenseKeyResponse();
			}, this)
		});
	},

	onLicenseKeyResponse: function()
	{
		this.loading = false;
		this.$newKeyBtn.removeClass('active');
		this.$spinner.addClass('hidden');
	},

	onUnsuccessfulLicenseKeyResponse: function()
	{
		alert(Craft.t('A new license key could not be generated for you at this time.'));
	}

});


})(jQuery);
