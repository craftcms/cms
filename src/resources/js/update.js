(function($) {


Craft.Updater = Garnish.Base.extend({

	$graphic: null,
	$status: null,
	$errorDetails: null,
	data: null,

	init: function(handle, manualUpdate)
	{
		this.$graphic = $('#graphic');
		this.$status = $('#status');

		if (!handle)
		{
			this.showError(Craft.t('Unable to determine what to update.'));
			return;
		}

		this.data = {
			handle: handle,
			manualUpdate: manualUpdate
		};

		this.postActionRequest('update/prepare');
	},

	updateStatus: function(msg)
	{
		this.$status.html(msg);
	},

	showError: function(msg)
	{
		this.updateStatus(msg);
		this.$graphic.addClass('error');
	},

	postActionRequest: function(action)
	{
		var data = {
			data: this.data
		};

		Craft.postActionRequest(action, data, $.proxy(function(response, textStatus) {

			if (textStatus == 'success' && response.alive)
			{
				this.onSuccessResponse(response);
			}
			else
			{
				this.onErrorResponse();
			}

		}, this), {
			complete: $.noop
		});
	},

	onSuccessResponse: function(response)
	{
		if (response.data)
		{
			this.data = response.data;
		}

		if (response.errorDetails)
		{
			this.$errorDetails = response.errorDetails;
		}

		if (response.nextStatus)
		{
			this.updateStatus(response.nextStatus);
		}

		if (response.nextAction)
		{
			this.postActionRequest(response.nextAction);
		}

		if (response.finished)
		{
			this.onFinish(response.returnUrl);
		}
	},

	onFinish: function(returnUrl)
	{
		if (this.$errorDetails)
		{
			this.$graphic.addClass('error');
			this.updateStatus(Craft.t('Craft was unable to install this update. :(') + '<br /><p>' + this.$errorDetails + '</p>');
		}
		else
		{
			this.updateStatus(Craft.t('All done!'));
			this.$graphic.addClass('success');

			// Redirect to the Dashboard in half a second
			setTimeout(function() {
				if (returnUrl) {
					window.location = Craft.getUrl(returnUrl);
				}
				else {
					window.location = Craft.getUrl('dashboard');
				}
			}, 500);
		}
	}
});


})(jQuery);
