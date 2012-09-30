(function($) {


Blocks.QuickPostWidget = Blocks.Base.extend({

	$widget: null,
	$form: null,
	$formClone: null,
	$spinner: null,
	$errorList: null,
	loading: false,

	init: function(widgetId, params)
	{
		this.$widget = $('#widget'+widgetId);
		this.$form = this.$widget.find('form:first');
		this.$formClone = this.$form.clone(true);
		this.$spinner = this.$form.find('.spinner');

		this.addListener(this.$form, 'submit', 'onSubmit');
	},

	onSubmit: function(event)
	{
		event.preventDefault();

		if (this.loading) return;
		this.loading = true;
		this.$spinner.show();

		var data = Blocks.getPostData(this.$form);
		data.enabled = 1;

		$.post(Blocks.actionUrl+'entries/saveEntry', data, $.proxy(function(response) {
			if (this.$errorList)
			{
				this.$errorList.children().remove();
			}

			if (response.success)
			{
				Blocks.cp.displayNotice(Blocks.t('Entry saved.'));
				var $newForm = this.$formClone.clone(true);
				this.$form.replaceWith($newForm);
				this.$form = $newForm;
			}
			else
			{
				Blocks.cp.displayError(Blocks.t('Couldn’t save entry.'));

				if (response.errors || response.blockErrors)
				{
					if (!this.$errorList)
					{
						this.$errorList = $('<ul class="errors"/>').insertAfter(this.$form);
					}

					var errors = $.extend({}, response.errors, response.blockErrors);

					for (var attribute in errors)
					{
						for (var i = 0; i < errors[attribute].length; i++)
						{
							var error = errors[attribute][i];
							$('<li>'+error+'</li>').appendTo(this.$errorList);
						}
					}
				}
			}

			this.loading = false;
			this.$spinner.hide();
		}, this));
	}
});


})(jQuery);
