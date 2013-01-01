(function($) {


Blocks.Updater = Blocks.Base.extend({

	$status: null,
	data: null,

	init: function(handle, manualUpdate)
	{
		this.$status = $('#status');

		if (!handle)
		{
			this.showError(Blocks.t('Unable to determine what to update.'));
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
		this.$status.addClass('error');
	},

	postActionRequest: function(action)
	{
		var data = {
			data: this.data
		};

		Blocks.postActionRequest(action, data, $.proxy(this, 'onSuccessResponse'), $.proxy(this, 'onErrorResponse'));
	},

	onSuccessResponse: function(response)
	{
		if (response.data)
		{
			this.data = response.data;
		}

		if (response.nextStatus)
		{
			this.updateStatus(response.nextStatus);
		}

		if (response.nextAction)
		{
			this.postActionRequest(response.nextAction);
		}

		if (response.error)
		{
			this.$status.addClass('error');
		}
		else if (response.finished)
		{
			this.onFinish();
		}
	},

	onErrorResponse: function()
	{
		this.showError(Blocks.t('An unknown error occurred. Rolling back…'));
		this.postActionRequest('update/rollback');
	},

	onFinish: function()
	{
		this.updateStatus(Blocks.t('All done!'));
		this.$status.addClass('success');

		// Redirect to the Dashboard in half a second
		setTimeout(function() {
			window.location = Blocks.getUrl('dashboard');
		}, 500);
	}
});


})(jQuery);
