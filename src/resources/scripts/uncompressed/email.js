(function($) {


var EmailSettingsForm = blx.Base.extend({

	$emailSettingsPane: null,
	$protocolSelect: null,
	$hiddenFields: null,
	$protocolSettingsPane: null,
	$protocolSettingsPaneHead: null,
	$protocolSettingsPaneBody: null,
	protocol: null,

	init: function()
	{
		this.$emailSettingsPane = $('#email-settings');
		this.$protocolSelect = $('#protocol');
		this.$hiddenFields = $('#hidden-fields');

		this._onEmailTypeChange();
		this.addListener(this.$protocolSelect, 'change', '_onEmailTypeChange');

		// Initialize Switch and Pill fields
		this.smtpAuthSwitch = new blx.ui.LightSwitch('#smtpAuth', {
			onChange: $.proxy(this, '_onSmtpAuthChange')
		});
	},

	_buildProtocolSettingsPane: function()
	{
		if (!this.$protocolSettingsPane)
		{
			this.$protocolSettingsPane = $('<div class="pane" />').insertAfter(this.$emailSettingsPane);
			var $head = $('<div class="head" />').appendTo(this.$protocolSettingsPane);
			this.$protocolSettingsPaneHead = $('<h5 />').appendTo($head);
			this.$protocolSettingsPaneBody = $('<div class="body" />').appendTo(this.$protocolSettingsPane);
		}
	},

	_onEmailTypeChange: function()
	{
		if (this.protocol && this.protocol in EmailSettingsForm.protocolFields)
		{
			// Detach the old fields
			for (var i = 0; i < EmailSettingsForm.protocolFields[this.protocol].length; i++)
			{
				$('#'+EmailSettingsForm.protocolFields[this.protocol][i]+'-field').appendTo(this.$hiddenFields)
			}
		}

		this.protocol = this.$protocolSelect.val();

		if (this.protocol in EmailSettingsForm.protocolFields)
		{
			this._buildProtocolSettingsPane();
			this.$protocolSettingsPane.show();

			// Update the heading
			var selectedIndex = this.$protocolSelect[0].selectedIndex,
				$selectedOption = $(this.$protocolSelect[0][selectedIndex]),
				label = $selectedOption.html();
			this.$protocolSettingsPaneHead.html(label+' Settings');

			// Attach the new fields
			for (var i = 0; i < EmailSettingsForm.protocolFields[this.protocol].length; i++)
			{
				$('#'+EmailSettingsForm.protocolFields[this.protocol][i]+'-field').appendTo(this.$protocolSettingsPaneBody);
			}
		}
		else
		{
			if (this.$protocolSettingsPane)
				this.$protocolSettingsPane.hide();
		}
	}

}, {
	protocolFields: {
		Smtp:      ['smtpAuth', 'smtpKeepAlive', 'smtpSecureTransportType', 'port', 'host', 'timeout'],
		Pop:       ['username', 'password', 'port', 'host', 'timeout'],
		GmailSmtp: ['username', 'password']
	}
});


blx.emailSettingsForm = new EmailSettingsForm();


})(jQuery);
