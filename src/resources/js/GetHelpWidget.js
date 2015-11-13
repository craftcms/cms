(function($) {


	Craft.GetHelpWidget = Garnish.Base.extend(
	{
		widgetId: 0,
		loading: false,

		$widget: null,
		$message: null,
		$fromEmail: null,
		$attachLogs: null,
		$attachDbBackup: null,
		$attachAdditionalFile: null,
		$sendBtn: null,
		$spinner: null,
		$error: null,
		$errorList: null,
		$iframe: null,

		init: function(widgetId)
		{
			this.widgetId = widgetId;

			Craft.GetHelpWidget.widgets[this.widgetId] = this;

			this.$widget = $('#widget'+widgetId);
			this.$message = this.$widget.find('.message:first');
			this.$fromEmail = this.$widget.find('.fromEmail:first');
			this.$attachLogs = this.$widget.find('.attachLogs:first');
			this.$attachDbBackup = this.$widget.find('.attachDbBackup:first');
			this.$attachAdditionalFile = this.$widget.find('.attachAdditionalFile:first');
			this.$sendBtn = this.$widget.find('.submit:first');
			this.$spinner = this.$widget.find('.buttons .spinner');
			this.$error = this.$widget.find('.error:first');
			this.$form = this.$widget.find('form:first');
			this.$form.prepend('<input type="hidden" name="widgetId" value="' + this.widgetId + '" />');
			this.$form.prepend(Craft.getCsrfInput());

			this.addListener(this.$sendBtn, 'activate', 'sendMessage');
		},

		sendMessage: function()
		{
			var iframeName = 'iframeWidget' + this.widgetId;

			if (this.loading) return;

			if (!this.$iframe)
			{
				this.$iframe = $('<iframe id="' + iframeName + '" name="' + iframeName + '" style="display: none" />').insertAfter(this.$form);
			}

			this.loading = true;
			this.$sendBtn.addClass('active');
			this.$spinner.removeClass('hidden');

			this.$form.attr('target', iframeName);
			this.$form.attr('action', Craft.getActionUrl('dashboard/sendSupportRequest'));

			this.$form.submit();
		},

		parseResponse: function(response)
		{
			this.loading = false;
			this.$sendBtn.removeClass('active');
			this.$spinner.addClass('hidden');

			if (this.$errorList)
			{
				this.$errorList.children().remove();
			}

			if (response.errors)
			{
				if (!this.$errorList)
				{
					this.$errorList = $('<ul class="errors"/>').insertAfter(this.$form);
				}

				for (var attribute in response.errors)
				{
					for (var i = 0; i < response.errors[attribute].length; i++)
					{
						var error = response.errors[attribute][i];
						$('<li>'+error+'</li>').appendTo(this.$errorList);
					}
				}
			}

			if (response.success)
			{
				Craft.cp.displayNotice(Craft.t('Message sent successfully.'));
				this.$message.val('');
				this.$attachAdditionalFile.val('');
			}

			this.$iframe.html('');
		}
	},
	{
		widgets: {}
	});


})(jQuery);
