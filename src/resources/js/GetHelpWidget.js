(function($) {


Craft.GetHelpWidget = Garnish.Base.extend({

	$widget: null,
	$message: null,
	$fromEmail: null,
	$attachDebugFiles: null,
	$sendBtn: null,
	$spinner: null,
	$error: null,
	originalBodyVal: null,
	originalFromVal: null,
	originalAttachDebugFilesVal: null,
	loading: false,
	$errorList: null,

	init: function(widgetId)
	{
		this.$widget = $('#widget'+widgetId);
		this.$message = this.$widget.find('.message:first');
		this.$fromEmail = this.$widget.find('.fromEmail:first');
		this.$attachDebugFiles= this.$widget.find('.attachDebugFiles:eq(2)');
		this.$sendBtn = this.$widget.find('.submit:first');
		this.$spinner = this.$widget.find('.spinner:first');
		this.$error = this.$widget.find('.error:first');
		this.$form = this.$widget.find('form:first');

		this.originalBodyVal = this.$message.val();
		this.originalFromVal = this.$fromEmail.val();
		this.originalAttachDebugFilesVal = this.$attachDebugFiles.val();

		this.addListener(this.$sendBtn, 'activate', 'sendMessage');
	},

	sendMessage: function()
	{
		if (this.loading) return;

		this.loading = true;
		this.$sendBtn.addClass('active');
		this.$spinner.removeClass('hidden');

		var data = {
			message: this.$message.val(),
			fromEmail: this.$fromEmail.val(),
			attachDebugFiles: this.$attachDebugFiles.val()
		};

		Craft.postActionRequest('dashboard/sendSupportRequest', data, $.proxy(function(response) {
			this.loading = false;
			this.$sendBtn.removeClass('active');
			this.$spinner.addClass('hidden');

			if (this.$errorList)
			{
				this.$errorList.children().remove();
			}

			if (response.success)
			{
				this.$message.val(this.originalBodyVal);
				this.$fromEmail.val(this.originalFromVal);
				this.$attachDebugFiles.val(this.originalAttachDebugFilesVal);
				Craft.cp.displayNotice(Craft.t('Message sent successfully.'));
			}
			else
			{
				Craft.cp.displayError(Craft.t('Couldn’t send support request.'));

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
			}
		}, this));
	}
});


})(jQuery);
