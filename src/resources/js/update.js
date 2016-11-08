(function($) {


Craft.Updater = Garnish.Base.extend(
{
	$graphic: null,
	$status: null,
	$errorDetails: null,
	data: null,

	init: function(data)
	{
		this.$graphic = $('#graphic');
		this.$status = $('#status');

		if (!data || !data.handle)
		{
			this.showError(Craft.t('Unable to determine what to update.'));
			return;
		}

		this.data = data;

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

		Craft.postActionRequest(action, data, $.proxy(function(response, textStatus, jqXHR)
		{
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
		var errorText =
			'<p>'+Craft.t('A fatal error has occurred:')+'</p>' +
			'<div id="error" class="code">' +
				'<p><strong class="code">'+Craft.t('Status:')+'</strong> '+Craft.escapeHtml(jqXHR.statusText)+'</p>' +
				'<p><strong class="code">'+Craft.t('Response:')+'</strong> '+Craft.escapeHtml(jqXHR.responseText)+'</p>' +
			'</div>' +
			'<a class="btn submit big" href="mailto:support@craftcms.com' +
				'?subject='+encodeURIComponent('Craft update failure') +
				'&body='+encodeURIComponent(
					'Describe what happened here.\n\n' +
					'-----------------------------------------------------------\n\n' +
					'Status: '+jqXHR.statusText+'\n\n' +
					'Response: '+jqXHR.responseText
				) +
			'">' +
				Craft.t('Send for help') +
			'</a>';

		this.updateStatus(errorText);
	},

	onFinish: function(returnUrl, rollBack)
	{
		if (this.$errorDetails)
		{
			this.$graphic.addClass('error');
			var errorText = Craft.t('Craft was unable to install this update :(') + '<br /><p>';

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
