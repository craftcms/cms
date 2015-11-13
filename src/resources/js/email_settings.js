(function($) {


var EmailSettingsForm = Garnish.Base.extend(
{
	$form: null,
	$protocolField: null,
	$protocolSelect: null,
	$hiddenFields: null,
	$testBtn: null,
	$testSpinner: null,
	$protocolSettingsPane: null,
	$protocolSettingsPaneHead: null,
	$protocolSettingsPaneBody: null,
	protocol: null,

	init: function()
	{
		this.$form = $('#container');
		this.$protocolField = $('#protocol-field');
		this.$protocolSelect = $('#protocol');
		this.$hiddenFields = $('#hidden-fields');
		this.$testBtn = $('#test');
		this.$testSpinner = $('#test-spinner');

		this._onEmailTypeChange();
		this.addListener(this.$protocolSelect, 'change', '_onEmailTypeChange');
		this.addListener(this.$testBtn, 'activate', 'sendTestEmail');
	},

	getField: function(fieldIndex)
	{
		return $('#'+EmailSettingsForm.protocolFields[this.protocol][fieldIndex]+'-field');
	},

	_onEmailTypeChange: function()
	{
		if (this.protocol && this.protocol in EmailSettingsForm.protocolFields)
		{
			// Detach the old fields
			for (var i = 0; i < EmailSettingsForm.protocolFields[this.protocol].length; i++)
			{
				this.getField(i).appendTo(this.$hiddenFields);
			}
		}

		this.protocol = this.$protocolSelect.val();

		if (this.protocol in EmailSettingsForm.protocolFields)
		{
			// Attach the new fields
			var $lastField = this.$protocolField;
			for (var j = 0; j < EmailSettingsForm.protocolFields[this.protocol].length; j++)
			{
				var $field = this.getField(j);
				$field.insertAfter($lastField);
				$lastField = $field;
			}
		}
	},

	sendTestEmail: function()
	{
		if (this.$testBtn.hasClass('sel')) return;

		this.$testBtn.addClass('sel');
		this.$testSpinner.removeClass('hidden');

		var data = Garnish.getPostData(this.$form);
		delete data.action;

		Craft.postActionRequest('systemSettings/testEmailSettings', data, $.proxy(function(response, textStatus)
		{
			this.$testBtn.removeClass('sel');
			this.$testSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.success)
				{
					Craft.cp.displayNotice(Craft.t('Email sent successfully! Check your inbox.'));
				}
				else
				{
					Craft.cp.displayError(response.error);
				}
			}
		}, this));
	}

}, {
	protocolFields: {
		smtp:  ['host', 'port', 'smtpKeepAlive', 'smtpAuth', 'smtpAuthCredentials', 'smtpSecureTransportType', 'timeout'],
		pop:   ['username', 'password', 'host', 'port', 'timeout'],
		gmail: ['username', 'password']
	}
});

Craft.emailSettingsForm = new EmailSettingsForm();


})(jQuery);
