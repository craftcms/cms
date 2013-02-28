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

		var data = {
			email: this.settings.email
		};

		$.ajax({
			url:     '@@@elliottEndpointUrl@@@actions/licenses/createLicense',
			data:    data,
			type:    'POST',

			success: $.proxy(function(response)
			{
				this.onLicenseKeyResponse();

				if (response.success)
				{
					this.$input.val(response.licenseKey);
				}
				else
				{
					this.onUnsuccessfulLicenseKeyResponse();
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
