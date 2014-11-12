(function($) {


Craft.AccountSettingsForm = Garnish.Base.extend(
{
	userId: null,
	isCurrent: null,

	$lockBtns: null,
	$currentPasswordInput: null,
	$currentPasswordSpinner: null,

	$deleteBtn: null,
	$deleteActionRadios: null,
	$deleteSpinner: null,

	currentPasswordModal: null,
	confirmDeleteModal: null,
	userSelect: null,
	_deleting: false,

	init: function(userId, isCurrent)
	{
		this.userId = userId;
		this.isCurrent = isCurrent;

		this.$lockBtns = $('.btn.lock');
		this.$deleteBtn = $('#delete-btn');

		this.addListener(this.$lockBtns, 'click', 'showCurrentPasswordForm');
		this.addListener(this.$deleteBtn, 'click', 'showConfirmDeleteModal');
	},

	showCurrentPasswordForm: function()
	{
		if (!this.currentPasswordModal)
		{
			var $form = $('<form id="verifypasswordmodal" class="modal fitted"/>').appendTo(Garnish.$bod),
				$body = $('<div class="body"><p>'+Craft.t(this.isCurrent ? 'Please enter your current password.' : 'Please enter your password.')+'</p></div>').appendTo($form),
				$passwordWrapper = $('<div class="passwordwrapper"/>').appendTo($body),
				$buttons = $('<div class="buttons right"/>').appendTo($body),
				$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons),
				$submitBtn = $('<input type="submit" class="btn submit" value="'+Craft.t('Continue')+'" />').appendTo($buttons);

			this.$currentPasswordInput = $('<input type="password" class="text password fullwidth"/>').appendTo($passwordWrapper);
			this.$currentPasswordSpinner = $('<div class="spinner hidden"/>').appendTo($buttons);
			this.currentPasswordModal = new Garnish.Modal($form);

			new Craft.PasswordInput(this.$currentPasswordInput, {
				onToggleInput: $.proxy(function($newPasswordInput) {
					this.$currentPasswordInput = $newPasswordInput;
				}, this)
			});

			this.addListener($cancelBtn, 'click', function() {
				this.currentPasswordModal.hide();
			});

			this.addListener($form, 'submit', 'submitCurrentPassword');
		}
		else
		{
			this.currentPasswordModal.show();
		}

		// Auto-focus the password input
		if (!Garnish.isMobileBrowser(true))
		{
			setTimeout($.proxy(function() {
				this.$currentPasswordInput.focus();
			}, this), 100);
		}
	},

	submitCurrentPassword: function(ev)
	{
		ev.preventDefault();

		var password = this.$currentPasswordInput.val();

		if (password)
		{
			this.$currentPasswordSpinner.removeClass('hidden');

			var data = {
				password: password
			};

			Craft.postActionRequest('users/verifyPassword', data, $.proxy(function(response, textStatus)
			{
				this.$currentPasswordSpinner.addClass('hidden');

				if (textStatus == 'success')
				{
					if (response.success)
					{
						$('<input type="hidden" name="password" value="'+password+'"/>').appendTo('#userform');
						var $newPasswordInput = $('#newPassword');
						$('#email').add($newPasswordInput).removeClass('disabled').removeAttr('disabled');
						this.$lockBtns.remove();

						new Craft.PasswordInput($newPasswordInput);

						this.currentPasswordModal.hide();
					}
					else
					{
						Garnish.shake(this.currentPasswordModal.$container);
					}
				}

			}, this));
		}
	},

	showConfirmDeleteModal: function()
	{
		if (!this.confirmDeleteModal)
		{
			this._createConfirmDeleteModal();
		}
		else
		{
			this.confirmDeleteModal.show();
		}

		// Auto-focus the first radio
		if (!Garnish.isMobileBrowser(true))
		{
			setTimeout($.proxy(function() {
				this.$deleteActionRadios.first().focus();
			}, this), 100);
		}
	},

	validateDeleteInputs: function()
	{
		var validates = false;

		if (this.$deleteActionRadios.eq(0).prop('checked'))
		{
			validates = !!this.userSelect.totalSelected;
		}
		else if (this.$deleteActionRadios.eq(1).prop('checked'))
		{
			validates = true;
		}

		if (validates)
		{
			this.$deleteSubmitBtn.removeClass('disabled')
		}
		else
		{
			this.$deleteSubmitBtn.addClass('disabled')
		}

		return validates;
	},

	submitDeleteUser: function(ev)
	{
		if (this._deleting || !this.validateDeleteInputs())
		{
			ev.preventDefault();
			return;
		}

		this.$deleteSubmitBtn.addClass('active');
		this.$deleteSpinner.removeClass('hidden');
		this.disable();
		this.userSelect.disable();
		this._deleting = true;
	},

	// Private Methods
	// =========================================================================

	_createConfirmDeleteModal: function()
	{
		var $form = $(
				'<form id="confirmdeletemodal" class="modal fitted" method="post" accept-charset="UTF-8">' +
					Craft.getCsrfInput() +
					'<input type="hidden" name="action" value="users/deleteUser"/>' +
					'<input type="hidden" name="userId" value="'+this.userId+'"/>' +
					'<input type="hidden" name="redirect" value="'+(Craft.edition == Craft.Pro ? 'users' : 'dashboard')+'"/>' +
				'</form>'
			).appendTo(Garnish.$bod),
			$body = $(
				'<div class="body">' +
					'<p>'+Craft.t('What do you want to do with the user’s content?')+'</p>' +
					'<div class="options">' +
						'<label><input type="radio" name="contentAction" value="transfer"/> '+Craft.t('Transfer it to:')+'</label>' +
						'<div id="transferselect" class="elementselect">' +
							'<div class="elements"></div>' +
							'<div class="btn add icon dashed">'+Craft.t('Choose a user')+'</div>' +
						'</div>' +
					'</div>' +
					'<div>' +
						'<label><input type="radio" name="contentAction" value="delete"/> '+Craft.t('Delete it')+'</label>' +
					'</div>' +
				'</div>'
			).appendTo($form),
			$buttons = $('<div class="buttons right"/>').appendTo($body),
			$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons);

		this.$deleteActionRadios = $body.find('input[type=radio]');
		this.$deleteSubmitBtn = $('<input type="submit" class="btn submit disabled" value="'+Craft.t('Delete user')+'" />').appendTo($buttons);
		this.$deleteSpinner = $('<div class="spinner hidden"/>').appendTo($buttons);

		this.confirmDeleteModal = new Garnish.Modal($form);

		this.userSelect = new Craft.BaseElementSelectInput({
			id: 'transferselect',
			name: 'transferContentTo',
			elementType: 'User',
			criteria: {
				id: 'not '+this.userId
			},
			limit: 1,
			modalSettings: {
				closeOtherModals: false
			},
			onSelectElements: $.proxy(function()
			{
				if (!this.$deleteActionRadios.first().prop('checked'))
				{
					this.$deleteActionRadios.first().click();
				}
				else
				{
					this.validateDeleteInputs();
				}
			}, this),
			onRemoveElements: $.proxy(this, 'validateDeleteInputs'),
			selectable: false,
			editable: false
		});

		this.addListener($cancelBtn, 'click', function() {
			this.confirmDeleteModal.hide();
		});

		this.addListener(this.$deleteActionRadios, 'change', 'validateDeleteInputs');
		this.addListener($form, 'submit', 'submitDeleteUser');
	}
});


})(jQuery)
