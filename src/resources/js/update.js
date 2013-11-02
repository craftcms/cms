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

		Craft.postActionRequest(action, data, $.proxy(function(response, textStatus, jqXHR) {

			if (textStatus == 'success' && response.alive)
			{
				this.onSuccessResponse(response);
			}
			else
			{
				this.onErrorResponse(jqXHR);
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
			var rollBack = false;

			if (response.rollBack)
			{
				rollBack = true;
			}

			this.onFinish(response.returnUrl, rollBack);
		}
	},

	onErrorResponse: function(jqXHR)
	{
		this.$graphic.addClass('error');
		var errorText = Craft.t('An error has occurred.  Please contact {email} and be sure to include the error message.', { email: '<a href="mailto:support@buildwithcraft.com?subject=Craft+Update+Failure">support@buildwithcraft.com</a>'} ) + '<br /><p>' + jqXHR.statusText + '</p><br /><p>' + jqXHR.responseText + '</p>';

		this.updateStatus(errorText);
	},

	onFinish: function(returnUrl, rollBack)
	{
		if (this.$errorDetails)
		{
			this.$graphic.addClass('error');
			var errorText = Craft.t('Craft was unable to install this update. :(') + '<br /><p>';

			if (rollBack)
			{
				errorText += Craft.t('The site has been restored to the state it was in before the attempted update.') + '</p><br /><p>';
			}
			else
			{
				errorText += Craft.t('No files have been updated and the database has not been touched.') + '</p><br /><p>';
			}

			errorText += this.$errorDetails + '</p>';
			this.updateStatus(errorText);
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
