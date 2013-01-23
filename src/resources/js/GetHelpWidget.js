(function($) {


Blocks.GetHelpWidget = Garnish.Base.extend({

	$widget: null,
	$message: null,
	$sendBtn: null,
	$spinner: null,
	$error: null,
	originalBodyVal: null,
	loading: false,

	init: function(widgetId)
	{
		this.$widget = $('#widget'+widgetId);
		this.$message = this.$widget.find('.message:first');
		this.$sendBtn = this.$widget.find('.submit:first');
		this.$spinner = this.$widget.find('.spinner:first');
		this.$error = this.$widget.find('.error:first');

		this.originalBodyVal = this.$message.val();

		this.addListener(this.$sendBtn, 'activate', 'sendMessage');
	},

	sendMessage: function()
	{
		if (this.loading) return;

		this.loading = true;
		this.$sendBtn.addClass('active');
		this.$spinner.removeClass('hidden');

		var data = {
			message: this.$message.val()
		};

		Blocks.postActionRequest('dashboard/sendSupportRequest', data, $.proxy(function(response) {
			this.loading = false;
			this.$sendBtn.removeClass('active');
			this.$spinner.addClass('hidden');

			if (response.success)
			{
				this.$message.val(this.originalBodyVal);
				Blocks.cp.displayNotice(Blocks.t('Message sent successfully.'));
			}
			else
			{
				this.$error.show();
			}
		}, this));
	}
});


})(jQuery);
