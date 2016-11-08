(function($) {


Craft.AccountSettingsForm = Garnish.Base.extend(
{
	userId: null,
	isCurrent: null,

	$copyPasswordResetUrlBtn: null,
	$actionSpinner: null,

	confirmDeleteModal: null,
	$deleteBtn: null,

	init: function(userId, isCurrent)
	{
		this.userId = userId;
		this.isCurrent = isCurrent;

		this.$copyPasswordResetUrlBtn = $('#copy-passwordreset-url');
		this.$actionSpinner = $('#action-spinner');
		this.$deleteBtn = $('#delete-btn');

		this.addListener(this.$copyPasswordResetUrlBtn, 'click', 'handleCopyPasswordResetUrlBtnClick');
		this.addListener(this.$deleteBtn, 'click', 'showConfirmDeleteModal');
	},

	handleCopyPasswordResetUrlBtnClick: function()
	{
		// Requires an elevated session
		Craft.elevatedSessionManager.requireElevatedSession($.proxy(this, 'getPasswordResetUrl'));
	},

	getPasswordResetUrl: function()
	{
		this.$actionSpinner.removeClass('hidden');

		var data = {
			userId: this.userId
		};

		Craft.postActionRequest('users/getPasswordResetUrl', data, $.proxy(function(response, textStatus)
		{
			this.$actionSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				var message = Craft.t('{ctrl}C to copy.', {
					ctrl: (navigator.appVersion.indexOf('Mac') ? '⌘' : 'Ctrl-')
				});

				prompt(message, response);
			}
		}, this));
	},

	showConfirmDeleteModal: function()
	{
		if (!this.confirmDeleteModal)
		{
			this.confirmDeleteModal = new Craft.DeleteUserModal(this.userId);
		}
		else
		{
			this.confirmDeleteModal.show();
		}
	}
});


})(jQuery);
